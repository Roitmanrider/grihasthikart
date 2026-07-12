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
    public function dashboard(): array
    {
        $today = now(config('app.timezone'))->toDateString();
        $monthStart = now(config('app.timezone'))->startOfMonth()->toDateString();

        $sales = $this->salesSummary($today, $monthStart);
        $purchase = $this->purchaseSummary($today, $monthStart);

        return [
            'sales' => $sales,
            'inventory' => $this->inventorySummary(),
            'purchase' => $purchase,
            'tax' => $this->taxSummary($sales['output_gst'], $purchase['input_gst']),
            'returns' => $this->returnsSummary(),
        ];
    }

    private function salesSummary(string $today, string $monthStart): array
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

        $validOrders = Order::query()->whereNotIn('order_status', $this->cancelledStatuses());

        return [
            'today_sales' => round((float) (clone $validOrders)->whereDate('placed_at', $today)->sum('grand_total'), 2),
            'month_sales' => round((float) (clone $validOrders)->whereDate('placed_at', '>=', $monthStart)->sum('grand_total'), 2),
            'total_orders' => Order::query()->count(),
            'delivered_orders' => Order::query()->where('order_status', 'delivered')->count(),
            'cancelled_orders' => Order::query()->whereIn('order_status', $this->cancelledStatuses())->count(),
            'return_refund_amount' => $this->returnRefundAmount(),
            'payment_methods' => $this->paymentMethodBreakdown(),
            'output_gst' => round((float) (clone $validOrders)->sum('tax_total'), 2),
        ];
    }

    private function inventorySummary(): array
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
                ->whereRaw('(quantity_on_hand - reserved_quantity - damaged_quantity) <= low_stock_threshold')
                ->count(),
            'out_of_stock_count' => Inventory::query()
                ->whereRaw('(quantity_on_hand - reserved_quantity - damaged_quantity) <= 0')
                ->count(),
            'stock_value' => $this->stockValue(),
        ];
    }

    private function purchaseSummary(string $today, string $monthStart): array
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

        return [
            'today_purchases' => round((float) PurchaseEntry::query()->whereDate('purchase_date', $today)->sum('grand_total'), 2),
            'month_purchases' => round((float) PurchaseEntry::query()->whereDate('purchase_date', '>=', $monthStart)->sum('grand_total'), 2),
            'supplier_totals' => $this->supplierPurchaseTotals(),
            'input_gst' => round((float) PurchaseEntry::query()->sum('gst_total'), 2),
            'available' => true,
        ];
    }

    private function returnsSummary(): array
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

        return [
            'requested' => ReturnRequest::query()->where('status', 'requested')->count(),
            'approved' => ReturnRequest::query()->where('status', 'approved')->count(),
            'rejected' => ReturnRequest::query()->where('status', 'rejected')->count(),
            'refunded' => ReturnRequest::query()->where('status', 'refunded')->count(),
            'refund_amount' => round((float) ReturnRequest::query()->sum('refund_amount'), 2),
            'available' => true,
        ];
    }

    private function taxSummary(float $outputGst, float $inputGst): array
    {
        return [
            'output_gst' => round($outputGst, 2),
            'input_gst' => round($inputGst, 2),
            'net_gst_payable' => round($outputGst - $inputGst, 2),
            'has_exact_output' => Schema::hasTable('orders') && Schema::hasColumn('orders', 'tax_total'),
            'has_exact_input' => Schema::hasTable('purchase_entries') && Schema::hasColumn('purchase_entries', 'gst_total'),
        ];
    }

    private function paymentMethodBreakdown(): array
    {
        $methods = $this->emptyPaymentMethods();

        Order::query()
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

    private function returnRefundAmount(): float
    {
        if (! Schema::hasTable('return_requests')) {
            return 0.0;
        }

        return round((float) ReturnRequest::query()->whereIn('status', ['approved', 'refunded', 'closed'])->sum('refund_amount'), 2);
    }

    private function stockValue(): float
    {
        if (! Schema::hasTable('product_variants')) {
            return 0.0;
        }

        return round((float) Inventory::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
            ->selectRaw('SUM(inventories.quantity_on_hand * COALESCE(product_variants.purchase_price, product_variants.selling_price, 0)) as stock_value')
            ->value('stock_value'), 2);
    }

    private function supplierPurchaseTotals(): array
    {
        if (! Schema::hasColumn('purchase_entries', 'supplier_id')) {
            return [];
        }

        $query = PurchaseEntry::query()
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
}
