<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\ProductImageRepositoryInterface;
use App\Models\ProductImage;

class ProductImageRepository extends BaseRepository implements ProductImageRepositoryInterface
{
    public function __construct(ProductImage $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 15)
    {
        $query = $this->baseQuery($filters);

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    public function forProduct(int $productId, array $filters = [])
    {
        return $this->baseQuery($filters)
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->get();
    }

    public function forVariant(int $productVariantId, array $filters = [])
    {
        return $this->baseQuery($filters)
            ->where('product_variant_id', $productVariantId)
            ->get();
    }

    public function findWithTrashed(int $id)
    {
        return $this->model
            ->withTrashed()
            ->findOrFail($id);
    }

    public function clearPrimaryForProduct(int $productId, ?int $exceptImageId = null): int
    {
        $query = $this->model
            ->newQuery()
            ->where('product_id', $productId)
            ->whereNull('product_variant_id')
            ->where('is_primary', true);

        if ($exceptImageId !== null) {
            $query->whereKeyNot($exceptImageId);
        }

        return $query->update(['is_primary' => false]);
    }

    public function clearPrimaryForVariant(int $productVariantId, ?int $exceptImageId = null): int
    {
        $query = $this->model
            ->newQuery()
            ->where('product_variant_id', $productVariantId)
            ->where('is_primary', true);

        if ($exceptImageId !== null) {
            $query->whereKeyNot($exceptImageId);
        }

        return $query->update(['is_primary' => false]);
    }

    public function restore(ProductImage $image): bool
    {
        return (bool) $image->restore();
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model
            ->newQuery()
            ->with(['product', 'variant'])
            ->search($filters['search'] ?? null);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        if (array_key_exists('is_primary', $filters) && $filters['is_primary'] !== null && $filters['is_primary'] !== '') {
            $query->where('is_primary', (bool) $filters['is_primary']);
        }

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['display_order', 'created_at', 'status', 'is_primary', 'title'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderByDesc('is_primary')
            ->orderBy($sort, $direction)
            ->orderBy('id');
    }
}
