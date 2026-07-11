<?php

namespace App\Domains\Report\Services;

use App\Domains\Order\Services\OrderStatusService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Collection;

class TaxReportService
{
    public function __construct(
        private readonly BusinessSettingService $settings,
        private readonly OrderStatusService $orderStatusService
    ) {}

    public function gstSummary(array $filters): array
    {
        $orders = $this->orders($filters)->get();
        $items = $orders->flatMap->items;
        $itemTotals = $this->summarizeItems($items);

        return array_merge($itemTotals, [
            'total_orders' => $orders->count(),
            'gross_order_amount' => round($orders->sum(fn ($order) => (float) $order->subtotal + (float) $order->delivery_charge), 2),
            'total_coupon_discount' => round($orders->sum('discount_total'), 2),
            'total_delivery_charge' => round($orders->sum('delivery_charge'), 2),
            'grand_total' => round($orders->sum('grand_total'), 2),
            'payment_split' => $orders
                ->groupBy('payment_method')
                ->map(fn ($orders) => [
                    'count' => $orders->count(),
                    'amount' => round($orders->sum('grand_total'), 2),
                ])
                ->all(),
        ]);
    }

    public function gstByRate(array $filters): Collection
    {
        $items = $this->orders($filters)->get()->flatMap->items;

        return $items
            ->groupBy(fn (OrderItem $item) => number_format((float) ($item->gst_rate_snapshot ?? $this->settings->get('tax.default_gst_rate', 0)), 2, '.', ''))
            ->map(function ($items, $rate) {
                $totals = $this->summarizeItems($items);

                return array_merge($totals, [
                    'gst_rate' => (float) $rate,
                    'order_item_count' => $items->count(),
                    'quantity_total' => round($items->sum(fn ($item) => (float) $item->quantity), 3),
                ]);
            })
            ->sortKeys()
            ->values();
    }

    public function monthly(array $filters): Collection
    {
        return $this->orders($filters)->get()
            ->groupBy(fn (Order $order) => ($order->placed_at ?: $order->created_at)->format('Y-m'))
            ->map(function ($orders, $month) {
                $itemTotals = $this->summarizeItems($orders->flatMap->items);

                return array_merge($itemTotals, [
                    'month' => $month,
                    'order_count' => $orders->count(),
                    'coupon_discounts' => round($orders->sum('discount_total'), 2),
                    'delivery_charges' => round($orders->sum('delivery_charge'), 2),
                    'grand_total' => round($orders->sum('grand_total'), 2),
                ]);
            })
            ->sortKeys()
            ->values();
    }

    public function orderTaxDetail(Order $order): array
    {
        $order->load('items');

        return [
            'order' => $order,
            'items' => $order->items->map(function (OrderItem $item) {
                $tax = $this->itemTax($item);

                return [
                    'item' => $item,
                    'gst_rate' => $this->itemRate($item),
                    'taxable_amount' => $tax['taxable_amount'],
                    'gst_amount' => $tax['gst_amount'],
                    'gross_amount' => $tax['gross_amount'],
                ];
            }),
            'totals' => $this->summarizeItems($order->items),
        ];
    }

    public function filters(array $input): array
    {
        return collect($input)
            ->only(['date_from', 'date_to', 'order_status', 'payment_status', 'payment_method'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function orders(array $filters)
    {
        return Order::query()
            ->with('items')
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('placed_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('placed_at', '<=', $date))
            ->when($filters['order_status'] ?? null, fn ($query, $status) => $query->where('order_status', $status))
            ->when(! ($filters['order_status'] ?? null), fn ($query) => $query->whereNotIn('order_status', $this->orderStatusService->cancelledStatuses()))
            ->when($filters['payment_status'] ?? null, fn ($query, $status) => $query->where('payment_status', $status))
            ->when(! ($filters['payment_status'] ?? null), fn ($query) => $query->whereNotIn('payment_status', ['failed', 'cancelled', 'refunded']))
            ->when($filters['payment_method'] ?? null, fn ($query, $method) => $query->where('payment_method', $method))
            ->latest('placed_at');
    }

    private function summarizeItems(Collection $items): array
    {
        $taxable = 0.0;
        $gst = 0.0;
        $gross = 0.0;

        foreach ($items as $item) {
            $tax = $this->itemTax($item);
            $taxable += $tax['taxable_amount'];
            $gst += $tax['gst_amount'];
            $gross += $tax['gross_amount'];
        }

        return [
            'total_item_subtotal' => round($items->sum('line_subtotal'), 2),
            'total_mrp' => round($items->sum('line_mrp_total'), 2),
            'taxable_amount' => round($taxable, 2),
            'total_gst_collected' => round($gst, 2),
            'gross_amount' => round($gross, 2),
        ];
    }

    private function itemTax(OrderItem $item): array
    {
        $rate = $this->itemRate($item);
        $gross = (float) $item->line_total;
        $snapshotTax = (float) $item->tax_amount;

        if ($snapshotTax > 0) {
            return [
                'gross_amount' => round($gross, 2),
                'gst_amount' => round($snapshotTax, 2),
                'taxable_amount' => round(max(0, $gross - $snapshotTax), 2),
            ];
        }

        if ($rate <= 0) {
            return [
                'gross_amount' => round($gross, 2),
                'gst_amount' => 0.0,
                'taxable_amount' => round($gross, 2),
            ];
        }

        if ((bool) $this->settings->get('tax.prices_include_gst', true)) {
            $taxable = $gross / (1 + ($rate / 100));
            $gst = $gross - $taxable;
        } else {
            $taxable = (float) $item->line_subtotal;
            $gst = $taxable * $rate / 100;
            $gross = $taxable + $gst;
        }

        return [
            'gross_amount' => round($gross, 2),
            'gst_amount' => round($gst, 2),
            'taxable_amount' => round($taxable, 2),
        ];
    }

    private function itemRate(OrderItem $item): float
    {
        return (float) ($item->gst_rate_snapshot ?? $this->settings->get('tax.default_gst_rate', 0));
    }
}
