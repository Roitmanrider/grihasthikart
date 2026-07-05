<?php

namespace App\Domains\Cashback\Services;

use App\Domains\Cashback\Contracts\CashbackRepositoryInterface;
use App\Models\CashbackLedger;
use App\Models\CashbackRule;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CashbackService
{
    public function __construct(
        private readonly CashbackRepositoryInterface $repository
    ) {}

    public function dashboardStats(): array
    {
        return $this->repository->dashboardStats();
    }

    public function defaultRule(): CashbackRule
    {
        return $this->repository->defaultRule()
            ?? CashbackRule::query()->create([
                'name' => 'Default Cashback Rule',
                'status' => true,
                'is_default' => true,
            ]);
    }

    public function createRule(array $data): CashbackRule
    {
        return DB::transaction(function () use ($data) {
            $data = $this->prepareRuleData($data);
            $this->validateRule($data);

            if ($data['is_default']) {
                CashbackRule::query()->where('is_default', true)->update(['is_default' => false]);
            }

            return CashbackRule::query()->create($data);
        });
    }

    public function updateRule(CashbackRule $rule, array $data): CashbackRule
    {
        return DB::transaction(function () use ($rule, $data) {
            $data = $this->prepareRuleData($data);
            $this->validateRule($data);

            if ($data['is_default']) {
                CashbackRule::query()->where('id', '!=', $rule->id)->where('is_default', true)->update(['is_default' => false]);
            }

            $rule->update($data);

            return $rule;
        });
    }

    public function balance(Customer $customer): float
    {
        return $this->repository->customerBalance($customer);
    }

    public function pendingAmount(Customer $customer): float
    {
        return $this->repository->pendingRedemptionAmount($customer);
    }

    public function availableBalance(Customer $customer): float
    {
        return max(0, $this->balance($customer) - $this->pendingAmount($customer));
    }

    public function writeLedger(Customer $customer, string $type, float $amount, ?int $orderId = null, ?int $couponId = null, ?int $redemptionRequestId = null, ?string $description = null, array $metadata = []): CashbackLedger
    {
        if (! in_array($type, CashbackLedger::TYPES, true)) {
            throw new InvalidArgumentException('Invalid cashback ledger type.');
        }

        $current = $this->balance($customer);
        $signedAmount = in_array($type, ['redeemed', 'reversed', 'adjustment_debit'], true) ? -abs($amount) : abs($amount);
        $balanceAfter = round($current + $signedAmount, 2);

        if ($balanceAfter < 0) {
            throw new InvalidArgumentException('Cashback balance cannot be negative.');
        }

        return CashbackLedger::query()->create([
            'customer_id' => $customer->id,
            'order_id' => $orderId,
            'coupon_id' => $couponId,
            'redemption_request_id' => $redemptionRequestId,
            'ledger_type' => $type,
            'amount' => abs($amount),
            'balance_after' => $balanceAfter,
            'description' => $description,
            'metadata' => $metadata ?: null,
            'created_by' => Auth::id(),
        ]);
    }

    private function prepareRuleData(array $data): array
    {
        return [
            'name' => $data['name'],
            'cashback_percent' => $data['cashback_percent'] ?? 5,
            'monthly_order_threshold' => $data['monthly_order_threshold'] ?? 5000,
            'eligible_category_threshold_percent' => $data['eligible_category_threshold_percent'] ?? 50,
            'redemption_multiple' => $data['redemption_multiple'] ?? 500,
            'processing_delay_days' => $data['processing_delay_days'] ?? 2,
            'status' => (bool) ($data['status'] ?? false),
            'is_default' => (bool) ($data['is_default'] ?? false),
            'metadata' => null,
        ];
    }

    private function validateRule(array $data): void
    {
        if ((float) $data['cashback_percent'] <= 0 || (float) $data['cashback_percent'] > 100) {
            throw new InvalidArgumentException('Cashback percent must be between 0 and 100.');
        }

        if ((float) $data['redemption_multiple'] <= 0) {
            throw new InvalidArgumentException('Redemption multiple must be greater than zero.');
        }
    }
}
