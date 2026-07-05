<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\AttributeRepositoryInterface;
use App\Models\Attribute;

class AttributeRepository extends BaseRepository implements AttributeRepositoryInterface
{
    public function __construct(Attribute $model)
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

        if (($filters['type'] ?? null) !== null && $filters['type'] !== '') {
            $query->where('type', $filters['type']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        if (array_key_exists('is_filterable', $filters) && $filters['is_filterable'] !== null && $filters['is_filterable'] !== '') {
            $query->where('is_filterable', (bool) $filters['is_filterable']);
        }

        if (array_key_exists('is_variant_defining', $filters) && $filters['is_variant_defining'] !== null && $filters['is_variant_defining'] !== '') {
            $query->where('is_variant_defining', (bool) $filters['is_variant_defining']);
        }

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['name', 'type', 'display_order', 'created_at', 'status'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeAttributes()
    {
        return $this->model
            ->active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function filterableAttributes()
    {
        return $this->model
            ->active()
            ->filterable()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function variantDefiningAttributes()
    {
        return $this->model
            ->active()
            ->variantDefining()
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

    public function idsInUse(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->whereHas('values')
            ->pluck('id')
            ->all();
    }
}
