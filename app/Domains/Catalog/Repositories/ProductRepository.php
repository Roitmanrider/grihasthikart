<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\ProductRepositoryInterface;
use App\Models\Product;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 15)
    {
        $query = $this->model
            ->newQuery()
            ->with(['brand', 'categories'])
            ->search($filters['search'] ?? null);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (($filters['brand_id'] ?? null) !== null && $filters['brand_id'] !== '') {
            $query->where('brand_id', (int) $filters['brand_id']);
        }

        if (($filters['category_id'] ?? null) !== null && $filters['category_id'] !== '') {
            $query->whereHas('categories', fn ($query) => $query->whereKey((int) $filters['category_id']));
        }

        foreach (['status', 'is_featured', 'is_trending', 'is_popular', 'is_new_arrival'] as $filter) {
            if (array_key_exists($filter, $filters) && $filters[$filter] !== null && $filters[$filter] !== '') {
                $query->where($filter, (bool) $filters[$filter]);
            }
        }

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'display_order', 'created_at', 'status', 'is_featured', 'is_trending', 'is_popular', 'is_new_arrival'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeProducts()
    {
        return $this->model
            ->active()
            ->with(['brand', 'categories'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function featuredProducts()
    {
        return $this->model
            ->active()
            ->featured()
            ->with(['brand', 'categories'])
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function findWithTrashed(int $id)
    {
        return $this->model
            ->withTrashed()
            ->findOrFail($id);
    }

    public function findWithRelations(int $id, array $relations = [])
    {
        return $this->model
            ->newQuery()
            ->with($relations)
            ->findOrFail($id);
    }

    public function syncCategories(Product $product, array $categoryPayload): void
    {
        $product->categories()->sync($categoryPayload);
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

    public function idsInUse(array $ids): array
    {
        return [];
    }
}
