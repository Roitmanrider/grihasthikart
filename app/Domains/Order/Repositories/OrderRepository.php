<?php

namespace App\Domains\Order\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Models\Order;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        return $this->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findByOrderNumberForSession(string $orderNumber, string $sessionId): Order
    {
        return $this->model
            ->newQuery()
            ->where('order_number', $orderNumber)
            ->where('session_id', $sessionId)
            ->with(['items', 'payment'])
            ->firstOrFail();
    }

    public function findWithDetails(int $id): Order
    {
        return $this->model
            ->newQuery()
            ->with(['items.productVariant', 'statusHistories.changer', 'payment'])
            ->findOrFail($id);
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model
            ->newQuery()
            ->withCount('items');

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function ($query) use ($search) {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_mobile', 'like', '%'.$search.'%');
            });
        }

        foreach (['order_status', 'payment_status', 'payment_method'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, $filters[$filter]);
            }
        }

        if (($filters['date_from'] ?? null) !== null && $filters['date_from'] !== '') {
            $query->whereDate('placed_at', '>=', $filters['date_from']);
        }

        if (($filters['date_to'] ?? null) !== null && $filters['date_to'] !== '') {
            $query->whereDate('placed_at', '<=', $filters['date_to']);
        }

        return $query->latest('placed_at')->latest();
    }
}
