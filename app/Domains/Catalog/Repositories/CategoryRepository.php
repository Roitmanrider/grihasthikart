<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\CategoryRepositoryInterface;
use App\Models\Category;

class CategoryRepository extends BaseRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 15)
    {
        $query = $this->model
            ->newQuery()
            ->with(['parent'])
            ->withCount('children')
            ->search($filters['search'] ?? null);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== null && $filters['is_featured'] !== '') {
            $query->where('is_featured', (bool) $filters['is_featured']);
        }

        if (array_key_exists('parent_id', $filters) && $filters['parent_id'] !== null && $filters['parent_id'] !== '') {
            if ($filters['parent_id'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', (int) $filters['parent_id']);
            }
        }

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'display_order', 'created_at', 'status'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function parentOptions(?int $excludeId = null)
    {
        $query = $this->model
            ->newQuery()
            ->orderBy('display_order')
            ->orderBy('name');

        if ($excludeId !== null) {
            $query->whereKeyNot($excludeId);
        }

        return $query->get();
    }

    public function rootCategories()
    {
        return $this->model
            ->root()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function activeCategories()
    {
        return $this->model
            ->active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function featuredCategories()
    {
        return $this->model
            ->active()
            ->featured()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function menuCategories()
    {
        return $this->model
            ->active()
            ->menu()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function homepageCategories()
    {
        return $this->model
            ->active()
            ->homepage()
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

    public function idsWithChildren(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->whereHas('children')
            ->pluck('id')
            ->all();
    }

    public function idsWithActiveChildren(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->whereHas('children', fn ($query) => $query->where('status', true))
            ->pluck('id')
            ->all();
    }
}
