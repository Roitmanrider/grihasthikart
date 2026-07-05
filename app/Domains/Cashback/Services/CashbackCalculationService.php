<?php

namespace App\Domains\Cashback\Services;

use App\Models\CashbackLedger;
use App\Models\CashbackMonthlySummary;
use App\Models\CashbackRule;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CashbackCalculationService
{
    public function __construct(
        private readonly CashbackService $cashbackService
    ) {}

    public function processPendingCashback(): int
    {
        $processed = 0;
        $rule = $this->cashbackService->defaultRule();

        Customer::query()
            ->where('cashback_enabled', true)
            ->chunkById(100, function ($customers) use (&$processed, $rule) {
                foreach ($customers as $customer) {
                    $months = $this->eligibleMonthsForCustomer($customer, $rule);

                    foreach ($months as $month) {
                        $processed += $this->processEligibleCashbackForMonth($customer, (int) $month['year'], (int) $month['month']);
                    }
                }
            });

        return $processed;
    }

    public function processEligibleCashbackForMonth(Customer $customer, int $year, int $month): int
    {
        return DB::transaction(function () use ($customer, $year, $month) {
            if (! $customer->cashback_enabled) {
                $this->writeSummary($customer, $year, $month, $this->emptySummary('not_eligible'));

                return 0;
            }

            $rule = $this->cashbackService->defaultRule();
            $orders = $this->eligibleOrders($customer, $year, $month, $rule)->get();

            if ($orders->isEmpty()) {
                $this->writeSummary($customer, $year, $month, $this->emptySummary('pending_window'));

                return 0;
            }

            $totals = $this->calculateMonthlyTotals($orders);
            $threshold = (float) ($customer->monthly_cashback_threshold ?: $rule->monthly_order_threshold);
            $categoryThreshold = (float) ($customer->category_cashback_threshold_percent ?: $rule->eligible_category_threshold_percent);
            $categoryPercent = $totals['total'] > 0 ? ($totals['category'] / $totals['total']) * 100 : 0;

            if ($totals['total'] < $threshold || $categoryPercent < $categoryThreshold) {
                $this->writeSummary($customer, $year, $month, array_merge($totals, [
                    'cashback_percent' => (float) $rule->cashback_percent,
                    'cashback_amount' => 0,
                    'eligibility_status' => 'not_eligible',
                ]));

                return 0;
            }

            $created = 0;
            $cashbackTotal = 0.0;

            foreach ($orders as $order) {
                if ($this->orderAlreadyProcessed($order)) {
                    continue;
                }

                $base = $this->cashbackBase($order);
                $cashback = round($base * (float) $rule->cashback_percent / 100, 2);

                if ($cashback <= 0) {
                    continue;
                }

                $this->cashbackService->writeLedger($customer, 'earned', $cashback, $order->id, description: 'Cashback earned for order '.$order->order_number);
                $cashbackTotal += $cashback;
                $created++;
            }

            $this->writeSummary($customer, $year, $month, array_merge($totals, [
                'cashback_percent' => (float) $rule->cashback_percent,
                'cashback_amount' => $cashbackTotal,
                'eligibility_status' => $created > 0 ? 'processed' : 'eligible',
            ]));

            return $created;
        });
    }

    public function eligibleOrders(Customer $customer, int $year, int $month, CashbackRule $rule)
    {
        return Order::query()
            ->with(['items.product.categories'])
            ->where('customer_id', $customer->id)
            ->where('order_status', 'delivered')
            ->whereIn('payment_status', ['paid', 'pending'])
            ->whereYear('delivered_at', $year)
            ->whereMonth('delivered_at', $month)
            ->where('delivered_at', '<=', now()->subDays((int) $rule->processing_delay_days));
    }

    public function cashbackBase(Order $order): float
    {
        return round(max(0, (float) $order->subtotal - (float) $order->discount_total), 2);
    }

    private function calculateMonthlyTotals($orders): array
    {
        $total = 0.0;
        $category = 0.0;
        $discounts = 0.0;
        $eligibleCategoryIds = $this->eligibleCategoryIds();

        foreach ($orders as $order) {
            $base = $this->cashbackBase($order);
            $total += (float) $order->subtotal;
            $discounts += (float) $order->discount_total;

            foreach ($order->items as $item) {
                $product = $item->product;
                $matches = $product?->categories->contains(fn ($category) => in_array($category->id, $eligibleCategoryIds, true));

                if ($matches) {
                    $category += (float) $item->line_total;
                }
            }
        }

        return [
            'total' => round($total, 2),
            'category' => round(min($category, $total), 2),
            'discounts' => round($discounts, 2),
            'base' => round(max(0, $total - $discounts), 2),
        ];
    }

    private function eligibleCategoryIds(): array
    {
        return Category::query()
            ->where(function ($query) {
                $query->where('name', 'like', '%Vegetable%')
                    ->orWhere('name', 'like', '%Fruit%')
                    ->orWhere('slug', 'like', '%vegetable%')
                    ->orWhere('slug', 'like', '%fruit%');
            })
            ->pluck('id')
            ->all();
    }

    private function eligibleMonthsForCustomer(Customer $customer, CashbackRule $rule): array
    {
        return Order::query()
            ->where('customer_id', $customer->id)
            ->where('order_status', 'delivered')
            ->where('delivered_at', '<=', now()->subDays((int) $rule->processing_delay_days))
            ->whereNotNull('delivered_at')
            ->get(['delivered_at'])
            ->map(fn ($order) => ['year' => (int) $order->delivered_at->year, 'month' => (int) $order->delivered_at->month])
            ->unique(fn ($month) => $month['year'].'-'.$month['month'])
            ->values()
            ->all();
    }

    private function orderAlreadyProcessed(Order $order): bool
    {
        return CashbackLedger::query()
            ->where('order_id', $order->id)
            ->where('ledger_type', 'earned')
            ->exists();
    }

    private function writeSummary(Customer $customer, int $year, int $month, array $data): void
    {
        CashbackMonthlySummary::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'year' => $year, 'month' => $month],
            [
                'total_delivered_order_amount' => $data['total'],
                'eligible_category_order_amount' => $data['category'],
                'coupon_discount_excluded_amount' => $data['discounts'],
                'eligible_cashback_base' => $data['base'],
                'cashback_percent' => $data['cashback_percent'],
                'cashback_amount' => $data['cashback_amount'],
                'eligibility_status' => $data['eligibility_status'],
                'processed_at' => now(),
            ]
        );
    }

    private function emptySummary(string $status): array
    {
        return [
            'total' => 0,
            'category' => 0,
            'discounts' => 0,
            'base' => 0,
            'cashback_percent' => 0,
            'cashback_amount' => 0,
            'eligibility_status' => $status,
        ];
    }
}
