<?php

namespace App\Domains\Customer\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Customer\Contracts\CustomerRepositoryInterface;
use App\Models\Customer;

class CustomerRepository extends BaseRepository implements CustomerRepositoryInterface
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        return $this->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithTrashed(int $id): Customer
    {
        return $this->model->withTrashed()->findOrFail($id);
    }

    public function findByMobile(string $mobile): ?Customer
    {
        return $this->model->newQuery()->where('mobile', $mobile)->first();
    }

    public function findWithDetails(int $id): Customer
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->with(['addresses' => fn ($query) => $query->withTrashed(), 'orders' => fn ($query) => $query->latest('placed_at')])
            ->findOrFail($id);
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->update(['status' => $status]);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return $this->model->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model->newQuery();

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('mobile', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        foreach (['status', 'is_premium', 'cashback_enabled'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, (bool) $filters[$filter]);
            }
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['name', 'mobile', 'created_at', 'status', 'is_premium'], true)) {
            $sort = 'created_at';
        }

        return $query->orderBy($sort, $direction);
    }
}
