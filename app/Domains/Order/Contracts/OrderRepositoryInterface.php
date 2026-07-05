<?php

namespace App\Domains\Order\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Order;

interface OrderRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function findByOrderNumberForSession(string $orderNumber, string $sessionId): Order;

    public function findWithDetails(int $id): Order;
}
