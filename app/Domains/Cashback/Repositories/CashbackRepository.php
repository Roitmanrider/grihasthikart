<?php

namespace App\Domains\Cashback\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Cashback\Contracts\CashbackRepositoryInterface;
use App\Models\CashbackLedger;
use App\Models\CashbackRedemptionRequest;
use App\Models\CashbackRule;
use App\Models\Customer;

class CashbackRepository extends BaseRepository implements CashbackRepositoryInterface
{
    public function __construct(CashbackRule $model)
    {
        parent::__construct($model);
    }

    public function dashboardStats(): array
    {
        return [
            'earned' => (float) CashbackLedger::query()->where('ledger_type', 'earned')->sum('amount'),
            'redeemed' => (float) CashbackLedger::query()->where('ledger_type', 'redeemed')->sum('amount'),
            'pending_redemptions' => CashbackRedemptionRequest::query()->where('status', 'pending')->count(),
            'customers_with_balance' => CashbackLedger::query()->select('customer_id')->groupBy('customer_id')->count(),
        ];
    }

    public function defaultRule(): ?CashbackRule
    {
        return CashbackRule::query()
            ->where('status', true)
            ->where('is_default', true)
            ->latest()
            ->first();
    }

    public function customerBalance(Customer $customer): float
    {
        return (float) (CashbackLedger::query()
            ->where('customer_id', $customer->id)
            ->latest('id')
            ->value('balance_after') ?? 0);
    }

    public function pendingRedemptionAmount(Customer $customer): float
    {
        return (float) CashbackRedemptionRequest::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('requested_amount');
    }

    public function customerLedgers(Customer $customer, int $perPage = 20)
    {
        return CashbackLedger::query()
            ->where('customer_id', $customer->id)
            ->latest()
            ->paginate($perPage);
    }

    public function pendingRedemptions(int $perPage = 20)
    {
        return CashbackRedemptionRequest::query()
            ->with('customer', 'coupon')
            ->latest()
            ->paginate($perPage);
    }
}
