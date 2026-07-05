<?php

namespace App\Domains\Payment\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Order;
use App\Models\Payment;

interface PaymentRepositoryInterface extends RepositoryInterface
{
    public function paginatedList(array $filters = [], int $perPage = 20);

    public function activeForOrder(Order $order): ?Payment;

    public function findWithDetails(int $id): Payment;
}
