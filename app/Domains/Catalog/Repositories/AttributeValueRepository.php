<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\AttributeValueRepositoryInterface;
use App\Models\AttributeValue;

class AttributeValueRepository extends BaseRepository implements AttributeValueRepositoryInterface
{
    public function __construct(AttributeValue $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 15)
    {
        $query = $this->model
            ->newQuery()
            ->with('attribute')
            ->search($filters['search'] ?? null);

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        if (($filters['attribute_id'] ?? null) !== null && $filters['attribute_id'] !== '') {
            $query->where('attribute_id', (int) $filters['attribute_id']);
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        $sort = $filters['sort'] ?? 'display_order';
        $direction = ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (! in_array($sort, ['value', 'display_order', 'created_at', 'status'], true)) {
            $sort = 'display_order';
        }

        return $query
            ->orderBy($sort, $direction)
            ->orderBy('value')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeValues()
    {
        return $this->model
            ->active()
            ->with('attribute')
            ->orderBy('display_order')
            ->orderBy('value')
            ->get();
    }

    public function valuesForAttribute(int $attributeId)
    {
        return $this->model
            ->active()
            ->where('attribute_id', $attributeId)
            ->orderBy('display_order')
            ->orderBy('value')
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
            ->whereHas('productVariants', fn ($query) => $query->withTrashed())
            ->pluck('id')
            ->all();
    }

    public function idsWithInactiveAttributes(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->whereHas('attribute', fn ($query) => $query->where('status', false))
            ->pluck('id')
            ->all();
    }

    public function activeIdsWithInactiveAttributes(array $ids): array
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->whereIn('id', $ids)
            ->where('status', true)
            ->whereHas('attribute', fn ($query) => $query->where('status', false))
            ->pluck('id')
            ->all();
    }
}
