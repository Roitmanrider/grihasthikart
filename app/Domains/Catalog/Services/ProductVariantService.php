<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\ProductVariantRepositoryInterface;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductVariantService
{
    public function __construct(
        private readonly ProductVariantRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function forProduct(Product $product, array $filters = [], int $perPage = 20)
    {
        return $this->repository->forProduct($product->id, $filters, $perPage);
    }

    public function create(Product $product, array $data)
    {
        return $this->persist(function (array $preparedData, array $attributePayload) use ($product) {
            return DB::transaction(function () use ($product, $preparedData, $attributePayload) {
                if ($preparedData['is_default']) {
                    $this->repository->clearDefaultForProduct($product->id);
                }

                /** @var ProductVariant $variant */
                $variant = $this->repository->create($preparedData);
                $this->repository->syncAttributeValues($variant, $attributePayload);
                $this->syncProductDefaultVariant($variant);

                return $variant;
            });
        }, $product, $data);
    }

    public function update(ProductVariant $variant, array $data)
    {
        return $this->persist(function (array $preparedData, array $attributePayload) use ($variant) {
            return DB::transaction(function () use ($variant, $preparedData, $attributePayload) {
                if ($preparedData['is_default']) {
                    $this->repository->clearDefaultForProduct($variant->product_id, $variant->id);
                }

                /** @var ProductVariant $updatedVariant */
                $updatedVariant = $this->repository->update($variant, $preparedData);
                $this->repository->syncAttributeValues($updatedVariant, $attributePayload);
                $this->syncProductDefaultVariant($updatedVariant);

                return $updatedVariant;
            });
        }, $variant->product, $data, $variant);
    }

    public function delete(ProductVariant $variant)
    {
        $this->ensureVariantsCanBeDeleted([$variant->id]);

        return DB::transaction(function () use ($variant) {
            $wasDefault = $variant->is_default;
            $product = $variant->product;
            $deleted = $this->repository->delete($variant);

            if ($wasDefault && $product->default_variant_id === $variant->id) {
                $product->forceFill(['default_variant_id' => null])->save();
            }

            return $deleted;
        });
    }

    public function restore(int $id)
    {
        $variant = $this->repository->findWithTrashed($id);

        if (! $variant->trashed()) {
            return $variant;
        }

        if ($variant->status) {
            $this->ensureActiveVariantCanBeRestored($variant);
        }

        DB::transaction(function () use ($variant) {
            $variant->restore();
            $this->normalizeDefaultVariant($variant);
        });

        return $variant;
    }

    public function bulkDelete(array $ids): int
    {
        $this->ensureVariantsCanBeDeleted($ids);

        return DB::transaction(function () use ($ids) {
            Product::query()
                ->whereIn('default_variant_id', $ids)
                ->update(['default_variant_id' => null]);

            return $this->repository->bulkDelete($ids);
        });
    }

    public function bulkDeleteForProduct(Product $product, array $ids): int
    {
        $this->ensureVariantsBelongToProduct($product, $ids);

        return $this->bulkDelete($ids);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        if ($status) {
            $this->ensureVariantsCanBeActivated($ids);
        }

        return $this->repository->bulkUpdateStatus($ids, $status);
    }

    public function bulkUpdateStatusForProduct(Product $product, array $ids, bool $status): int
    {
        $this->ensureVariantsBelongToProduct($product, $ids);

        return $this->bulkUpdateStatus($ids, $status);
    }

    public function bulkRestore(array $ids): int
    {
        $this->ensureVariantsCanBeActivated($ids);

        return DB::transaction(function () use ($ids) {
            $count = $this->repository->bulkRestore($ids);

            foreach ($this->repository->defaultVariantsByIds($ids) as $variant) {
                $this->normalizeDefaultVariant($variant);
            }

            return $count;
        });
    }

    public function bulkRestoreForProduct(Product $product, array $ids): int
    {
        $this->ensureVariantsBelongToProduct($product, $ids);

        return $this->bulkRestore($ids);
    }

    private function persist(callable $operation, Product $product, array $data, ?ProductVariant $variant = null)
    {
        $prepared = $this->prepareData($product, $data, $variant);

        try {
            return $operation($prepared['variant'], $prepared['attributes']);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                throw new InvalidArgumentException('A product variant with the same SKU, barcode, or attribute combination already exists.');
            }

            throw $exception;
        }
    }

    private function prepareData(Product $product, array $data, ?ProductVariant $variant = null): array
    {
        $status = (bool) ($data['status'] ?? $variant?->status ?? true);
        $isDefault = (bool) ($data['is_default'] ?? $variant?->is_default ?? false);
        $submittedAttributes = array_filter($data['attribute_values'] ?? []);
        $attributeValueIds = array_values(array_unique(array_map('intval', $submittedAttributes)));

        $this->ensurePricesAreValid($data);
        $this->ensureActiveVariantHasActiveProduct($product, $status);
        $isDefault = $this->resolveDefaultState($product, $isDefault, $variant);

        $attributePayload = $this->prepareAttributePayload($submittedAttributes, $status);
        $attributeSignature = $this->generateAttributeSignature($attributeValueIds);

        if ($this->repository->existsCombination($product->id, $attributeSignature, $variant?->id)) {
            throw new InvalidArgumentException('This product already has a variant with the same attribute combination.');
        }

        unset($data['attribute_values']);

        $data['product_id'] = $product->id;
        $data['attribute_signature'] = $attributeSignature;
        $data['barcode'] = trim((string) ($data['barcode'] ?? '')) !== '' ? $data['barcode'] : null;
        $data['weight'] = $data['weight'] ?? null;
        $data['purchase_price'] = $data['purchase_price'] ?? null;
        $data['is_default'] = $isDefault;
        $data['status'] = $status;
        $data['display_order'] = $data['display_order'] ?? 0;

        return [
            'variant' => $data,
            'attributes' => $attributePayload,
        ];
    }

    private function prepareAttributePayload(array $submittedAttributes, bool $status): array
    {
        $attributeValueIds = array_values(array_unique(array_map('intval', $submittedAttributes)));

        if ($attributeValueIds === []) {
            return [];
        }

        $values = AttributeValue::query()
            ->with('attribute')
            ->whereIn('id', $attributeValueIds)
            ->get()
            ->keyBy('id');

        if ($values->count() !== count($attributeValueIds)) {
            throw new InvalidArgumentException('One or more selected attribute values are invalid.');
        }

        $payload = [];

        foreach ($submittedAttributes as $submittedAttributeId => $attributeValueId) {
            $submittedAttributeId = (int) $submittedAttributeId;
            $attributeValueId = (int) $attributeValueId;

            /** @var AttributeValue $value */
            $value = $values[$attributeValueId];
            $attribute = $value->attribute;

            if ($value->attribute_id !== $submittedAttributeId) {
                throw new InvalidArgumentException('Selected attribute value does not belong to the submitted attribute.');
            }

            if (! $attribute->is_variant_defining) {
                throw new InvalidArgumentException('Only variant-defining attributes can be used for product variants.');
            }

            if ($status && (! $attribute->status || ! $value->status)) {
                throw new InvalidArgumentException('Active variants cannot use inactive attributes or attribute values.');
            }

            $payload[$attributeValueId] = ['attribute_id' => $attribute->id];
        }

        return $payload;
    }

    public function generateAttributeSignature(array $attributeValueIds): string
    {
        $attributeValueIds = array_values(array_unique(array_map('intval', $attributeValueIds)));
        sort($attributeValueIds);

        return $attributeValueIds === [] ? 'default' : implode('|', $attributeValueIds);
    }

    private function ensurePricesAreValid(array $data): void
    {
        $mrp = (float) ($data['mrp'] ?? 0);
        $sellingPrice = (float) ($data['selling_price'] ?? 0);
        $purchasePrice = $data['purchase_price'] ?? null;

        if ($sellingPrice > $mrp) {
            throw new InvalidArgumentException('Selling price cannot be greater than MRP.');
        }

        if ($purchasePrice !== null && $purchasePrice !== '' && (float) $purchasePrice > $mrp) {
            throw new InvalidArgumentException('Purchase price cannot be greater than MRP.');
        }
    }

    private function ensureActiveVariantHasActiveProduct(Product $product, bool $status): void
    {
        if ($status && ! $product->status) {
            throw new InvalidArgumentException('An active variant cannot belong to an inactive product.');
        }
    }

    private function ensureVariantsCanBeDeleted(array $ids): void
    {
        if ($this->repository->idsInUse($ids) !== []) {
            throw new InvalidArgumentException('Product variants linked to transactional records cannot be deleted.');
        }

        if ($this->repository->idsBlockingDefaultDelete($ids) !== []) {
            throw new InvalidArgumentException('Default variants cannot be deleted until another default variant is assigned.');
        }
    }

    private function ensureVariantsCanBeActivated(array $ids): void
    {
        if ($this->repository->idsBelongingToInactiveProducts($ids) !== []) {
            throw new InvalidArgumentException('Variants under inactive products cannot be activated.');
        }

        if ($this->repository->idsWithInactiveAttributeData($ids) !== []) {
            throw new InvalidArgumentException('Variants with inactive attributes or values cannot be activated.');
        }
    }

    private function ensureVariantsBelongToProduct(Product $product, array $ids): void
    {
        if ($this->repository->idsNotBelongingToProduct($product->id, $ids) !== []) {
            throw new InvalidArgumentException('Selected variants do not belong to this product.');
        }
    }

    private function resolveDefaultState(Product $product, bool $isDefault, ?ProductVariant $variant): bool
    {
        if ($variant === null && $this->repository->countForProduct($product->id) === 0) {
            return true;
        }

        if ($variant === null && $this->repository->defaultForProduct($product->id) === null) {
            return true;
        }

        if ($variant !== null && $variant->is_default && ! $isDefault) {
            throw new InvalidArgumentException('Assign another default variant before unsetting the current default.');
        }

        return $isDefault;
    }

    private function ensureActiveVariantCanBeRestored(ProductVariant $variant): void
    {
        $this->ensureActiveVariantHasActiveProduct($variant->product, true);

        if ($this->repository->idsWithInactiveAttributeData([$variant->id]) !== []) {
            throw new InvalidArgumentException('Active variants with inactive attributes or values cannot be restored.');
        }
    }

    private function syncProductDefaultVariant(ProductVariant $variant): void
    {
        if ($variant->is_default) {
            $variant->product->forceFill(['default_variant_id' => $variant->id])->save();
        }
    }

    private function normalizeDefaultVariant(ProductVariant $variant): void
    {
        if (! $variant->is_default) {
            return;
        }

        $this->repository->clearDefaultForProduct($variant->product_id, $variant->id);
        $this->syncProductDefaultVariant($variant);
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'product_variants_sku_unique')
            || str_contains($exception->getMessage(), 'product_variants_barcode_unique')
            || str_contains($exception->getMessage(), 'product_variants_product_id_attribute_signature_unique')
            || str_contains($exception->getMessage(), 'UNIQUE constraint failed')
            || str_contains($exception->getMessage(), 'Duplicate entry');
    }
}
