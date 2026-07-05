<?php

namespace App\Domains\Delivery\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Delivery\Contracts\DeliverySlotRepositoryInterface;
use App\Models\DeliverySlot;

class DeliverySlotRepository extends BaseRepository implements DeliverySlotRepositoryInterface
{
    public function __construct(DeliverySlot $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        $query = $this->model->newQuery();

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')
                ->orWhere('display_label', 'like', '%'.$search.'%'));
        }

        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        return $query->orderBy('display_order')->orderBy('start_time')->paginate($perPage)->withQueryString();
    }

    public function activeSlots()
    {
        return $this->model->newQuery()
            ->active()
            ->orderBy('display_order')
            ->orderBy('start_time')
            ->get();
    }

    public function findWithTrashed(int $id): DeliverySlot
    {
        return $this->model->withTrashed()->findOrFail($id);
    }

    public function hasOverlap(string $startTime, string $endTime, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->active()
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function bulkRestore(array $ids): int
    {
        return $this->model->onlyTrashed()->whereIn('id', $ids)->restore();
    }
}
