<?php

namespace App\Domains\Report\Services;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportDashboardService
{
    public function dashboard(?int $stockLocationId = null): array
    {
        $today = now(config('app.timezone'))->toDateString();
        $monthStart = now(config('app.timezone'))->startOfMonth()->toDateString();

        $sales = $this->salesSummary($today, $monthStart, $stockLocationId);
        $purchase = $this->purchaseSummary($today, $monthStart, $stockLocationId);

        return [
            'sales' => $sales,
            'inventory' => $this->inventorySummary($stockLocationId),
            'purchase' => $purchase,
            'tax' => $this->taxSummary($sales['output_gst'], $purchase['input_gst']),
            'returns' => $this->returnsSummary($stockLocationId),
        ];
    }

    private function salesSummary(string $today, string $monthStart, ?int $stockLocationId): array
    {
        if (! Schema::hasTable('orders')) {
            return [
                'today_sales' => 0.0,
                'month_sales' => 0.0,
                'total_orders' => 0,
                'delivered_orders' => 0,
                'cancelled_orders' => 0,
                'return_refund_amount' => 0.0,
                'payment_methods' => $this->emptyPaymentMethods(),
                'output_gst' => 0.0,
            ];
        }

        $validOrders = $this->scopeStore(Order::query(), $stockLocationId)->whereNotIn('order_status', $this->cancelledStatuses());
        $orders = $this->scopeStore(Order::query(), $stockLocationId);

        return [
            'today_sales' => round((float) (clone $validOrders)->whereDate('placed_at', $today)->sum('grand_total'), 2),
            'month_sales' => round((float) (clone $validOrders)->whereDate('placed_at', '>=', $monthStart)->sum('grand_total'), 2),
            'total_orders' => (clone $orders)->count(),
            'delivered_orders' => (clone $orders)->where('order_status', 'delivered')->count(),
            'cancelled_orders' => (clone $orders)->whereIn('order_status', $this->cancelledStatuses())->count(),
            'return_refund_amount' => $this->returnRefundAmount($stockLocationId),
            'payment_methods' => $this->paymentMethodBreakdown($stockLocationId),
            'output_gst' => round((float) (clone $validOrders)->sum('tax_total'), 2),
        ];
    }

    private function inventorySummary(?int $stockLocationId): array
    {
        if (! Schema::hasTable('inventories')) {
            return [
                'total_products' => $this->safeCount('products'),
                'total_variants' => $this->safeCount('product_variants'),
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
                'stock_value' => 0.0,
            ];
        }

        return [
            'total_products' => $this->safeCount('products'),
            'total_variants' => $this->safeCount('product_variants'),
            'low_stock_count' => Inventory::query()
                ->when($stockLocationId, fn ($query) => $query->where('stock_location_id', $stockLocationId))
                ->whereRaw('(quantity_on_hand - reserved_quantity - damaged_quantity) <= low_stock_threshold')
                ->count(),
            'out_of_stock_count' => Inventory::query()
                ->when($stockLocationId, fn ($query) => $query->where('stock_location_id', $stockLocationId))
                ->whereRaw('(quantity_on_hand - reserved_quantity - damaged_quantity) <= 0')
                ->count(),
            'stock_value' => $this->stockValue($stockLocationId),
        ];
    }

    private function purchaseSummary(string $today, string $monthStart, ?int $stockLocationId): array
    {
        if (! Schema::hasTable('purchase_entries')) {
            return [
                'today_purchases' => 0.0,
                'month_purchases' => 0.0,
                'supplier_totals' => [],
                'input_gst' => 0.0,
                'available' => false,
            ];
        }

        $purchases = PurchaseEntry::query()->when($stockLocationId, fn ($query) => $query->where('stock_location_id', $stockLocationId));

        return [
            'today_purchases' => round((float) (clone $purchases)->whereDate('purchase_date', $today)->sum('grand_total'), 2),
            'month_purchases' => round((float) (clone $purchases)->whereDate('purchase_date', '>=', $monthStart)->sum('grand_total'), 2),
            'supplier_totals' => $this->supplierPurchaseTotals($stockLocationId),
            'input_gst' => round((float) (clone $purchases)->sum('gst_total'), 2),
            'available' => true,
        ];
    }

    private function returnsSummary(?int $stockLocationId): array
    {
        if (! Schema::hasTable('return_requests')) {
            return [
                'requested' => 0,
                'approved' => 0,
                'rejected' => 0,
                'refunded' => 0,
                'refund_amount' => 0.0,
                'available' => false,
            ];
        }

        $returns = ReturnRequest::query()
            ->when($stockLocationId, fn ($query) => $query->whereHas('order', fn ($query) => $query->where('stock_location_id', $stockLocationId)));

        return [
            'requested' => (clone $returns)->where('status', 'requested')->count(),
            'approved' => (clone $returns)->where('status', 'approved')->count(),
            'rejected' => (clone $returns)->where('status', 'rejected')->count(),
            'refunded' => (clone $returns)->where('status', 'refunded')->count(),
            'refund_amount' => round((float) (clone $returns)->sum('refund_amount'), 2),
            'available' => true,
        ];
    }

    private function taxSummary(float $outputGst, float $inputGst): array
    {
        $inputCgst = $this->purchaseInputSplit('cgst_total', $inputGst);
        $inputSgst = $this->purchaseInputSplit('sgst_total', $inputGst);
        $outputCgst = round($outputGst / 2, 2);
        $outputSgst = round($outputGst / 2, 2);

        return [
            'output_gst' => round($outputGst, 2),
            'input_gst' => round($inputGst, 2),
            'output_cgst' => $outputCgst,
            'output_sgst' => $outputSgst,
            'input_cgst' => $inputCgst,
            'input_sgst' => $inputSgst,
            'net_cgst_payable' => round($outputCgst - $inputCgst, 2),
            'net_sgst_payable' => round($outputSgst - $inputSgst, 2),
            'net_gst_payable' => round($outputGst - $inputGst, 2),
            'has_exact_output' => Schema::hasTable('orders') && Schema::hasColumn('orders', 'tax_total'),
            'has_exact_input' => Schema::hasTable('purchase_entries') && Schema::hasColumn('purchase_entries', 'gst_total'),
        ];
    }

    private function purchaseInputSplit(string $column, float $inputGst): float
    {
        if (! Schema::hasTable('purchase_entries') || ! Schema::hasColumn('purchase_entries', $column)) {
            return round($inputGst / 2, 2);
        }

        $amount = round((float) PurchaseEntry::query()->sum($column), 2);

        return $amount > 0 ? $amount : round($inputGst / 2, 2);
    }

    private function paymentMethodBreakdown(?int $stockLocationId): array
    {
        $methods = $this->emptyPaymentMethods();

        $this->scopeStore(Order::query(), $stockLocationId)
            ->select('payment_method', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(grand_total) as amount_total'))
            ->groupBy('payment_method')
            ->get()
            ->each(function ($row) use (&$methods): void {
                $method = $row->payment_method ?: 'unknown';
                $methods[$method] = [
                    'count' => (int) $row->orders_count,
                    'amount' => round((float) $row->amount_total, 2),
                ];
            });

        return $methods;
    }

    private function emptyPaymentMethods(): array
    {
        return [
            'cod' => ['count' => 0, 'amount' => 0.0],
            'qr' => ['count' => 0, 'amount' => 0.0],
            'razorpay' => ['count' => 0, 'amount' => 0.0],
        ];
    }

    private function returnRefundAmount(?int $stockLocationId): float
    {
        if (! Schema::hasTable('return_requests')) {
            return 0.0;
        }

        return round((float) ReturnRequest::query()
            ->when($stockLocationId, fn ($query) => $query->whereHas('order', fn ($query) => $query->where('stock_location_id', $stockLocationId)))
            ->whereIn('status', ['approved', 'refunded', 'closed'])
            ->sum('refund_amount'), 2);
    }

    private function stockValue(?int $stockLocationId): float
    {
        if (! Schema::hasTable('product_variants')) {
            return 0.0;
        }

        return round((float) Inventory::query()
            ->when($stockLocationId, fn ($query) => $query->where('inventories.stock_location_id', $stockLocationId))
            ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
            ->selectRaw('SUM(inventories.quantity_on_hand * COALESCE(product_variants.purchase_price, product_variants.selling_price, 0)) as stock_value')
            ->value('stock_value'), 2);
    }

    private function supplierPurchaseTotals(?int $stockLocationId): array
    {
        if (! Schema::hasColumn('purchase_entries', 'supplier_id')) {
            return [];
        }

        $query = PurchaseEntry::query()
            ->when($stockLocationId, fn ($query) => $query->where('purchase_entries.stock_location_id', $stockLocationId))
            ->select('purchase_entries.supplier_id', DB::raw('COUNT(*) as purchases_count'), DB::raw('SUM(grand_total) as amount_total'))
            ->when(Schema::hasTable('suppliers'), function ($query) {
                $query->leftJoin('suppliers', 'suppliers.id', '=', 'purchase_entries.supplier_id')
                    ->addSelect('suppliers.name as supplier_name');
            })
            ->groupBy('purchase_entries.supplier_id')
            ->when(Schema::hasTable('suppliers'), fn ($query) => $query->groupBy('suppliers.name'))
            ->orderByDesc('amount_total')
            ->limit(5);

        return $query->get()
            ->map(fn ($row) => [
                'supplier_id' => $row->supplier_id,
                'supplier' => $row->supplier_name ?? ($row->supplier_id ? 'Supplier #'.$row->supplier_id : 'No supplier'),
                'count' => (int) $row->purchases_count,
                'amount' => round((float) $row->amount_total, 2),
            ])
            ->all();
    }

    private function safeCount(string $table): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return match ($table) {
            'products' => Product::query()->count(),
            'product_variants' => ProductVariant::query()->count(),
            default => DB::table($table)->count(),
        };
    }

    private function cancelledStatuses(): array
    {
        return ['cancelled', 'cancelled_by_admin', 'cancelled_by_customer'];
    }

    private function scopeStore($query, ?int $stockLocationId)
    {
        return $query->when($stockLocationId, fn ($query) => $query->where('stock_location_id', $stockLocationId));
    }
}
