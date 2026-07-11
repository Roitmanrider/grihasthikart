<?php

namespace App\Domains\Order\Services;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Order;
use Illuminate\Support\Facades\Schema;

class OrderDocumentService
{
    public function __construct(
        private readonly BusinessSettingService $settings
    ) {}

    public function invoiceData(Order $order): array
    {
        $order->loadMissing(['items.productVariant', 'payment']);

        return [
            'order' => $order,
            'business' => $this->settings->businessSettings(),
            'tax' => $this->settings->taxSettings(),
            'invoiceNumber' => $this->invoiceNumber($order),
            'billingAddress' => $this->deliveryAddress($order),
            'gstSummary' => $this->gstSummary($order),
            'paymentNote' => $this->paymentNote($order),
            'termsNote' => 'This is a computer-generated invoice. Goods once delivered are subject to the published return and refund policy.',
        ];
    }

    public function pickingSlipData(Order $order): array
    {
        $order->loadMissing(['items.productVariant.inventories.stockLocation']);

        return [
            'order' => $order,
            'deliveryAddress' => $this->deliveryAddress($order),
            'items' => $order->items,
        ];
    }

    public function packingSlipData(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'order' => $order,
            'deliveryAddress' => $this->deliveryAddress($order),
            'items' => $order->items,
        ];
    }

    public function deliveryAddress(Order $order): string
    {
        return collect([
            $order->delivery_address_line1,
            $order->delivery_address_line2,
            $order->delivery_landmark,
            $order->delivery_city,
            $order->delivery_state,
            $order->delivery_pincode,
        ])->filter()->implode(', ');
    }

    private function invoiceNumber(Order $order): string
    {
        if (Schema::hasColumn('orders', 'invoice_number') && filled($order->getAttribute('invoice_number'))) {
            return (string) $order->getAttribute('invoice_number');
        }

        return 'INV-'.$order->order_number;
    }

    private function gstSummary(Order $order): array
    {
        return $order->items
            ->groupBy(fn ($item) => number_format((float) ($item->gst_rate_snapshot ?? 0), 2, '.', ''))
            ->map(function ($items, string $rate): array {
                $taxable = $items->sum(fn ($item) => max(0, (float) $item->line_subtotal - (float) $item->tax_amount));
                $tax = $items->sum(fn ($item) => (float) $item->tax_amount);

                return [
                    'rate' => (float) $rate,
                    'taxable_value' => round($taxable, 2),
                    'gst_amount' => round($tax, 2),
                    'total' => round($taxable + $tax, 2),
                ];
            })
            ->values()
            ->all();
    }

    private function paymentNote(Order $order): string
    {
        return match ($order->payment_method) {
            'cod' => 'Cash on Delivery. Please collect payment as per order payment status.',
            'qr' => 'QR payment order. Verify payment status before handover.',
            'razorpay' => 'Online payment order. Verify payment status before handover.',
            default => 'Please verify payment status before handover.',
        };
    }
}
