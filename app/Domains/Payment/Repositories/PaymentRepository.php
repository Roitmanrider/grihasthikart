<?php

namespace App\Domains\Payment\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Payment\Contracts\PaymentRepositoryInterface;
use App\Models\Order;
use App\Models\Payment;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        return $this->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeForOrder(Order $order): ?Payment
    {
        return $this->model
            ->newQuery()
            ->where('order_id', $order->id)
            ->latest()
            ->first();
    }

    public function findWithDetails(int $id): Payment
    {
        return $this->model
            ->newQuery()
            ->with(['order.items', 'transactions.creator', 'verifier'])
            ->findOrFail($id);
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model
            ->newQuery()
            ->with('order');

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('payment_number', 'like', '%'.$search.'%')
                    ->orWhere('gateway_order_id', 'like', '%'.$search.'%')
                    ->orWhere('gateway_payment_id', 'like', '%'.$search.'%')
                    ->orWhereHas('order', function ($query) use ($search) {
                        $query->where('order_number', 'like', '%'.$search.'%')
                            ->orWhere('customer_name', 'like', '%'.$search.'%')
                            ->orWhere('customer_mobile', 'like', '%'.$search.'%');
                    });
            });
        }

        foreach (['payment_method', 'payment_status'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (($filters['date_from'] ?? null) !== null && $filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? null) !== null && $filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest();
    }
}
