<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\BrandRepositoryInterface;
use App\Models\Brand;

class BrandRepository extends BaseRepository implements BrandRepositoryInterface
{
    public function __construct(Brand $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 15)
    {
        $query = $this->model
            ->newQuery()
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

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'display_order', 'created_at', 'status', 'is_featured'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeBrands()
    {
        return $this->model
            ->active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function featuredBrands()
    {
        return $this->model
            ->active()
            ->featured()
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
}
