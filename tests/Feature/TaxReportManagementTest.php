<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\PurchaseEntry;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaxReportManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_gst_summary_page_loads_for_admin_and_blocks_unauthorized_user(): void
    {
        $this->orderWithItem();
        PurchaseEntry::query()->create([
            'supplier_id' => null,
            'purchase_number' => 'PUR-GST-001',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 100,
            'discount_total' => 0,
            'cgst_total' => 2,
            'sgst_total' => 3,
            'gst_total' => 5,
            'grand_total' => 105,
            'status' => PurchaseEntry::STATUS_POSTED,
        ]);

        $this->actingAs($this->admin)->get(route('admin.reports.gst-summary'))
            ->assertOk()
            ->assertSee('GST Summary Report')
            ->assertSee('GST report uses item-level tax snapshots')
            ->assertSee('Output CGST')
            ->assertSee('Input CGST')
            ->assertSee('Net CGST Payable')
            ->assertSee('Total Net GST Payable');

        $user = User::factory()->create(['email' => 'customer@example.com']);
        $this->actingAs($user)->get(route('admin.reports.gst-summary'))->assertForbidden();
    }

    public function test_gst_summary_excludes_cancelled_orders_by_default_and_filter_can_include_them(): void
    {
        $this->orderWithItem(orderOverrides: ['order_status' => 'placed']);
        $this->orderWithItem(orderOverrides: ['order_status' => 'cancelled']);

        $this->actingAs($this->admin)->get(route('admin.reports.gst-summary'))
            ->assertOk()
            ->assertSee('Rs. 105.00')
            ->assertDontSee('Rs. 210.00');

        $this->actingAs($this->admin)->get(route('admin.reports.gst-summary', ['order_status' => 'cancelled']))
            ->assertOk()
            ->assertSee('Rs. 105.00');
    }

    public function test_gst_by_rate_and_monthly_reports_group_correctly(): void
    {
        $this->orderWithItem(rate: 5, gross: 105, tax: 5, placedAt: now()->startOfMonth());
        $this->orderWithItem(rate: 12, gross: 112, tax: 12, placedAt: now()->subMonth()->startOfMonth());

        $this->actingAs($this->admin)->get(route('admin.reports.gst-by-rate'))
            ->assertOk()
            ->assertSee('5.00%')
            ->assertSee('12.00%')
            ->assertSee('Output CGST')
            ->assertSee('Output SGST');

        $this->actingAs($this->admin)->get(route('admin.reports.gst-monthly'))
            ->assertOk()
            ->assertSee(now()->format('Y-m'))
            ->assertSee(now()->subMonth()->format('Y-m'))
            ->assertSee('Input CGST')
            ->assertSee('Net SGST');
    }

    public function test_order_tax_detail_page_loads(): void
    {
        $order = $this->orderWithItem(rate: 18, gross: 118, tax: 18);

        $this->actingAs($this->admin)->get(route('admin.orders.tax', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('18.00%')
            ->assertSee('Rs. 18.00');
    }

    public function test_tax_calculation_supports_inclusive_and_exclusive_prices(): void
    {
        $inclusive = $this->orderWithItem(rate: 18, gross: 118, tax: 0);
        $this->actingAs($this->admin)->get(route('admin.orders.tax', $inclusive))
            ->assertOk()
            ->assertSee('Rs. 100.00')
            ->assertSee('Rs. 18.00');

        app(BusinessSettingService::class)->set('tax.prices_include_gst', false);
        $exclusive = $this->orderWithItem(rate: 18, gross: 100, tax: 0);
        $this->actingAs($this->admin)->get(route('admin.orders.tax', $exclusive))
            ->assertOk()
            ->assertSee('Rs. 118.00');
    }

    public function test_coupon_and_cashback_coupon_discounts_are_shown_separately(): void
    {
        $coupon = Coupon::factory()->create(['source' => 'cashback', 'is_cashback_coupon' => true]);
        $order = $this->orderWithItem(orderOverrides: [
            'coupon_id' => $coupon->id,
            'coupon_code_snapshot' => $coupon->code,
            'coupon_discount_amount' => 50,
            'discount_total' => 50,
            'grand_total' => 55,
        ]);

        $this->actingAs($this->admin)->get(route('admin.reports.gst-summary'))
            ->assertOk()
            ->assertSee('Rs. 50.00');

        $this->actingAs($this->admin)->get(route('admin.orders.tax', $order))
            ->assertOk()
            ->assertSee('Order discount: Rs. 50.00');
    }

    public function test_reports_use_order_item_snapshots_not_live_product_gst_rate(): void
    {
        $product = Product::factory()->create(['gst_rate' => 18]);
        $this->orderWithItem(product: $product, rate: 5, gross: 105, tax: 5);
        $product->update(['gst_rate' => 28]);

        $this->actingAs($this->admin)->get(route('admin.reports.gst-by-rate'))
            ->assertOk()
            ->assertSee('5.00%')
            ->assertDontSee('28.00%');
    }

    public function test_no_disallowed_modules_or_stock_fields_are_created(): void
    {
        $this->assertFalse(class_exists('App\\Models\\PurchaseGst'));
        $this->assertFalse(class_exists('App\\Models\\InvoicePdf'));

        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }

    private function orderWithItem(float $rate = 5, float $gross = 105, float $tax = 5, ?Product $product = null, ?Carbon $placedAt = null, array $orderOverrides = []): Order
    {
        $product ??= Product::factory()->create(['gst_rate' => $rate]);
        $order = Order::factory()->create(array_merge([
            'order_status' => 'placed',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'subtotal' => $gross,
            'total_mrp' => $gross,
            'delivery_charge' => 0,
            'discount_total' => 0,
            'grand_total' => $gross,
            'placed_at' => $placedAt ?: now(),
        ], $orderOverrides));

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'gst_rate_snapshot' => $rate,
            'line_subtotal' => $gross,
            'line_total' => $gross,
            'line_mrp_total' => $gross,
            'tax_amount' => $tax,
            'quantity' => 1,
        ]);

        return $order;
    }
}
