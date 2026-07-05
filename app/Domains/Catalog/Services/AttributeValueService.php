<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Domains\Catalog\Contracts\AttributeValueRepositoryInterface;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Services\SlugService;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use RuntimeException;

class AttributeValueService
{
    public function __construct(
        private readonly AttributeValueRepositoryInterface $repository,
        private readonly AttributeRepositoryInterface $attributeRepository,
        private readonly SlugService $slugService
    ) {
    }

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function activeValues()
    {
        return $this->repository->activeValues();
    }

    public function valuesForAttribute(int $attributeId)
    {
        return $this->repository->valuesForAttribute($attributeId);
    }

    public function create(array $data)
    {
        return $this->persistWithUniqueSlug(function (array $preparedData) {
            return $this->repository->create($preparedData);
        }, $data);
    }

    public function update(AttributeValue $attributeValue, array $data)
    {
        return $this->persistWithUniqueSlug(function (array $preparedData) use ($attributeValue) {
            return $this->repository->update($attributeValue, $preparedData);
        }, $data, $attributeValue);
    }

    public function delete(AttributeValue $attributeValue)
    {
        $this->ensureValuesCanBeDeleted([$attributeValue->id]);

        return $this->repository->delete($attributeValue);
    }

    public function restore(int $id)
    {
        $attributeValue = $this->repository->findWithTrashed($id);

        if (! $attributeValue->trashed()) {
            return $attributeValue;
        }

        if ($attributeValue->status) {
            $this->ensureActiveValuesCanBeRestored([$attributeValue->id]);
        }

        $attributeValue->restore();

        return $attributeValue;
    }

    public function bulkDelete(array $ids): int
    {
        $this->ensureValuesCanBeDeleted($ids);

        return $this->repository->bulkDelete($ids);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        if ($status) {
            $this->ensureValuesCanBeActivated($ids);
        }

        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkRestore(array $ids): int
    {
        $this->ensureActiveValuesCanBeRestored($ids);

        return $this->repository->bulkRestore($ids);
    }

    private function persistWithUniqueSlug(callable $operation, array $data, ?AttributeValue $attributeValue = null)
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $preparedData = $this->prepareData($data, $attributeValue, $attempt);

            try {
                return $operation($preparedData);
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to persist attribute value with unique values.');
    }

    private function prepareData(array $data, ?AttributeValue $attributeValue = null, int $attempt = 0): array
    {
        $attribute = $this->attributeRepository->find((int) $data['attribute_id']);
        $status = (bool) ($data['status'] ?? false);

        $this->ensureActiveValueHasActiveAttribute($attribute, $status);
        $this->ensureValueMatchesAttributeType($attribute, (string) $data['value']);

        $slugSource = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['value'];

        $data['slug'] = $this->slugService->generateScoped(
            $slugSource,
            AttributeValue::class,
            ['attribute_id' => $attribute->id],
            $attributeValue?->id,
            'slug',
            $attempt
        );

        $data['display_order'] = $data['display_order'] ?? 0;
        $data['status'] = $status;

        return $data;
    }

    private function ensureActiveValueHasActiveAttribute(Attribute $attribute, bool $status): void
    {
        if ($status && ! $attribute->status) {
            throw new InvalidArgumentException('An active attribute value cannot belong to an inactive attribute.');
        }
    }

    private function ensureValueMatchesAttributeType(Attribute $attribute, string $value): void
    {
        if ($attribute->type === 'number' && ! is_numeric($value)) {
            throw new InvalidArgumentException('Number attribute values must be numeric.');
        }

        if ($attribute->type === 'boolean' && ! in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no'], true)) {
            throw new InvalidArgumentException('Boolean attribute values must be true or false.');
        }
    }

    private function ensureValuesCanBeDeleted(array $ids): void
    {
        if ($this->repository->idsInUse($ids) !== []) {
            throw new InvalidArgumentException('Attribute values used by product variants cannot be deleted.');
        }
    }

    private function ensureValuesCanBeActivated(array $ids): void
    {
        if ($this->repository->idsWithInactiveAttributes($ids) !== []) {
            throw new InvalidArgumentException('Attribute values under inactive attributes cannot be activated.');
        }
    }

    private function ensureActiveValuesCanBeRestored(array $ids): void
    {
        if ($this->repository->activeIdsWithInactiveAttributes($ids) !== []) {
            throw new InvalidArgumentException('Active attribute values under inactive attributes cannot be restored.');
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'attribute_values_attribute_id_value_unique')
            || str_contains($exception->getMessage(), 'attribute_values_attribute_id_slug_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: attribute_values.attribute_id, attribute_values.value')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: attribute_values.attribute_id, attribute_values.slug')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
