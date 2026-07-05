<?php

namespace App\Domains\Coupon\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Coupon\Contracts\CouponRepositoryInterface;
use App\Models\Coupon;

class CouponRepository extends BaseRepository implements CouponRepositoryInterface
{
    public function __construct(Coupon $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        return $this->baseQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function findByCode(string $code): ?Coupon
    {
        return $this->model->newQuery()
            ->where('code', $code)
            ->first();
    }

    public function findWithDetails(int $id): Coupon
    {
        return $this->model->newQuery()
            ->with(['customer', 'usages.order'])
            ->withCount('usages')
            ->withTrashed()
            ->findOrFail($id);
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model->newQuery()
            ->with('customer')
            ->withCount('usages');

        if (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        } elseif (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        }

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('mobile', 'like', '%'.$search.'%');
                    });
            });
        }

        foreach (['discount_type', 'source'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        foreach (['status', 'is_cashback_coupon'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, (bool) $filters[$filter]);
            }
        }

        if (($filters['customer_specific'] ?? null) === '1') {
            $query->whereNotNull('customer_id');
        } elseif (($filters['customer_specific'] ?? null) === '0') {
            $query->whereNull('customer_id');
        }

        if (($filters['validity'] ?? null) === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<', now());
        } elseif (($filters['validity'] ?? null) === 'upcoming') {
            $query->whereNotNull('starts_at')->where('starts_at', '>', now());
        } elseif (($filters['validity'] ?? null) === 'active') {
            $query->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            });
        }

        return $query->latest();
    }
}
