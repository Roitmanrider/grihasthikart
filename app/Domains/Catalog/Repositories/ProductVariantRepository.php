<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\ProductVariantRepositoryInterface;
use App\Models\ProductVariant;

class ProductVariantRepository extends BaseRepository implements ProductVariantRepositoryInterface
{
    public function __construct(ProductVariant $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 15)
    {
        return $this->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forProduct(int $productId, array $filters = [], int $perPage = 15)
    {
        return $this->baseQuery($filters)
            ->where('product_id', $productId)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeForProduct(int $productId)
    {
        return $this->model
            ->newQuery()
            ->active()
            ->where('product_id', $productId)
            ->with(['attributeValues.attribute'])
            ->orderBy('display_order')
            ->orderBy('variant_name')
            ->get();
    }

    public function findWithTrashed(int $id)
    {
        return $this->model
            ->withTrashed()
            ->findOrFail($id);
    }

    public function findBySku(string $sku)
    {
        return $this->model
            ->newQuery()
            ->where('sku', $sku)
            ->first();
    }

    public function findByBarcode(string $barcode)
    {
        return $this->model
            ->newQuery()
            ->where('barcode', $barcode)
            ->first();
    }

    public function defaultForProduct(int $productId)
    {
        return $this->model
            ->newQuery()
            ->where('product_id', $productId)
            ->where('is_default', true)
            ->first();
    }

    public function defaultVariantsByIds(array $ids)
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->where('is_default', true)
            ->get();
    }

    public function existsCombination(int $productId, string $attributeSignature, ?int $ignoreId = null): bool
    {
        $query = $this->model
            ->newQuery()
            ->where('product_id', $productId)
            ->where('attribute_signature', $attributeSignature);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function clearDefaultForProduct(int $productId, ?int $exceptVariantId = null): int
    {
        $query = $this->model
            ->newQuery()
            ->where('product_id', $productId)
            ->where('is_default', true);

        if ($exceptVariantId !== null) {
            $query->whereKeyNot($exceptVariantId);
        }

        return $query->update(['is_default' => false]);
    }

    public function syncAttributeValues(ProductVariant $variant, array $attributePayload): void
    {
        $variant->attributeValues()->sync($attributePayload);
    }

    public function countForProduct(int $productId): int
    {
        return $this->model
            ->newQuery()
            ->where('product_id', $productId)
            ->count();
    }

    public function idsInUse(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->where(fn ($query) => $query->whereHas('inventories')->orWhereHas('cartItems'))
            ->pluck('id')
            ->all();
    }

    public function idsBelongingToInactiveProducts(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->whereHas('product', fn ($query) => $query->where('status', false))
            ->pluck('id')
            ->all();
    }

    public function idsWithInactiveAttributeData(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->where(function ($query) {
                $query->whereHas('attributes', fn ($query) => $query->where('status', false))
                    ->orWhereHas('attributeValues', fn ($query) => $query->where('status', false));
            })
            ->pluck('id')
            ->all();
    }

    public function idsBlockingDefaultDelete(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->where('is_default', true)
            ->where(function ($query) use ($ids) {
                $query->where('status', true)
                    ->orWhereHas('product.variants', fn ($query) => $query->whereNotIn('id', $ids));
            })
            ->pluck('id')
            ->all();
    }

    public function idsNotBelongingToProduct(int $productId, array $ids): array
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->where('product_id', '!=', $productId)
            ->pluck('id')
            ->all();
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->delete();
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    public function bulkRestore(array $ids): int
    {
        return $this->model
            ->onlyTrashed()
            ->whereIn('id', $ids)
            ->restore();
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model
            ->newQuery()
            ->with(['product.brand', 'attributeValues.attribute'])
            ->search($filters['search'] ?? null);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (($filters['product_id'] ?? null) !== null && $filters['product_id'] !== '') {
            $query->where('product_id', (int) $filters['product_id']);
        }

        foreach (['status', 'is_default'] as $filter) {
            if (array_key_exists($filter, $filters) && $filters[$filter] !== null && $filters[$filter] !== '') {
                $query->where($filter, (bool) $filters[$filter]);
            }
        }

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['variant_name', 'sku', 'mrp', 'selling_price', 'display_order', 'created_at', 'status', 'is_default'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('variant_name');
    }
}
