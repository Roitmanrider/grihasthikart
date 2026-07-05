<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Models\Attribute;
use App\Services\SlugService;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use RuntimeException;

class AttributeService
{
    public function __construct(
        private readonly AttributeRepositoryInterface $repository,
        private readonly SlugService $slugService
    ) {
    }

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function activeAttributes()
    {
        return $this->repository->activeAttributes();
    }

    public function filterableAttributes()
    {
        return $this->repository->filterableAttributes();
    }

    public function variantDefiningAttributes()
    {
        return $this->repository->variantDefiningAttributes();
    }

    public function create(array $data)
    {
        return $this->persistWithUniqueSlug(function (array $preparedData) {
            return $this->repository->create($preparedData);
        }, $data);
    }

    public function update(Attribute $attribute, array $data)
    {
        return $this->persistWithUniqueSlug(function (array $preparedData) use ($attribute) {
            return $this->repository->update($attribute, $preparedData);
        }, $data, $attribute);
    }

    public function delete(Attribute $attribute)
    {
        $this->ensureAttributesCanBeDeleted([$attribute->id]);

        return $this->repository->delete($attribute);
    }

    public function restore(int $id)
    {
        $attribute = $this->repository->findWithTrashed($id);

        if (! $attribute->trashed()) {
            return $attribute;
        }

        $attribute->restore();

        return $attribute;
    }

    public function bulkDelete(array $ids): int
    {
        $this->ensureAttributesCanBeDeleted($ids);

        return $this->repository->bulkDelete($ids);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->repository->bulkRestore($ids);
    }

    private function persistWithUniqueSlug(callable $operation, array $data, ?Attribute $attribute = null)
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $preparedData = $this->prepareData($data, $attribute, $attempt);

            try {
                return $operation($preparedData);
            } catch (QueryException $exception) {
                if (! $this->isUniqueConstraintViolation($exception) || $attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Unable to persist attribute with unique values.');
    }

    private function prepareData(array $data, ?Attribute $attribute = null, int $attempt = 0): array
    {
        $this->ensureValidType($data['type'] ?? null);

        $slugSource = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['name'];

        $data['slug'] = $this->slugService->generate(
            $slugSource,
            Attribute::class,
            $attribute?->id,
            'slug',
            $attempt
        );

        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_filterable'] = (bool) ($data['is_filterable'] ?? false);
        $data['is_variant_defining'] = (bool) ($data['is_variant_defining'] ?? false);
        $data['status'] = (bool) ($data['status'] ?? false);

        return $data;
    }

    private function ensureValidType(?string $type): void
    {
        if ($type === null || ! Attribute::isValidType($type)) {
            throw new InvalidArgumentException('Invalid attribute type.');
        }
    }

    private function ensureAttributesCanBeDeleted(array $ids): void
    {
        if ($this->repository->idsInUse($ids) !== []) {
            throw new InvalidArgumentException('Attributes used by attribute values or product variants cannot be deleted.');
        }
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'attributes_name_unique')
            || str_contains($exception->getMessage(), 'attributes_slug_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: attributes.name')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed: attributes.slug')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
