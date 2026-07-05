<?php

namespace App\Domains\Cashback\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\CashbackRule;
use App\Models\Customer;

interface CashbackRepositoryInterface extends RepositoryInterface
{
    public function dashboardStats(): array;

    public function defaultRule(): ?CashbackRule;

    public function customerBalance(Customer $customer): float;

    public function pendingRedemptionAmount(Customer $customer): float;

    public function customerLedgers(Customer $customer, int $perPage = 20);

    public function pendingRedemptions(int $perPage = 20);
}
