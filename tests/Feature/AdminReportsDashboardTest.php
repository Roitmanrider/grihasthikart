<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\ReturnRequest;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_guest_blocked(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_reports_page_loads_and_renders_summaries(): void
    {
        $this->seedDashboardData();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports Dashboard')
            ->assertSee('Sales Summary')
            ->assertSee('Inventory Summary')
            ->assertSee('Purchase Summary')
            ->assertSee('Tax Summary')
            ->assertSee('Returns Summary')
            ->assertSee('COD')
            ->assertSee('Razorpay')
            ->assertSee('Quick Links');
    }

    public function test_report_handles_empty_database_safely(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports Dashboard')
            ->assertSee('Rs. 0.00')
            ->assertSee('No supplier purchases.');
    }

    public function test_purchase_and_returns_summaries_render_when_tables_exist(): void
    {
        PurchaseEntry::query()->create([
            'supplier_id' => null,
            'purchase_number' => 'PUR-TEST-001',
            'bill_number' => 'BILL-1',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 100,
            'gst_total' => 18,
            'discount_total' => 0,
            'grand_total' => 118,
            'status' => 'posted',
        ]);

        $order = Order::factory()->create(['customer_id' => Customer::factory()->create()->id]);
        ReturnRequest::query()->create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'return_number' => 'RET-TEST-001',
            'status' => 'refunded',
            'requested_at' => now(),
            'refund_amount' => 50,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Rs. 118.00')
            ->assertSee('Rs. 18.00')
            ->assertSee('Refunded Returns')
            ->assertSee('Rs. 50.00');
    }

    private function seedDashboardData(): void
    {
        Order::factory()->create([
            'payment_method' => 'cod',
            'payment_status' => 'paid',
            'order_status' => 'delivered',
            'grand_total' => 250,
            'tax_total' => 12,
            'placed_at' => now(),
            'delivered_at' => now(),
        ]);
        Order::factory()->create([
            'payment_method' => 'razorpay',
            'payment_status' => 'paid',
            'order_status' => 'cancelled_by_customer',
            'grand_total' => 100,
            'tax_total' => 5,
            'placed_at' => now(),
            'cancelled_at' => now(),
        ]);

        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'purchase_price' => 20,
            'selling_price' => 30,
        ]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_location_id' => StockLocation::factory()->create()->id,
            'quantity_on_hand' => 5,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'low_stock_threshold' => 10,
        ]);

        PurchaseEntry::query()->create([
            'supplier_id' => null,
            'purchase_number' => 'PUR-TEST-002',
            'bill_number' => 'BILL-2',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 200,
            'gst_total' => 36,
            'discount_total' => 0,
            'grand_total' => 236,
            'status' => 'posted',
        ]);

        $order = Order::factory()->create([
            'customer_id' => Customer::factory()->create()->id,
            'order_status' => 'delivered',
        ]);
        ReturnRequest::query()->create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'return_number' => 'RET-TEST-002',
            'status' => 'requested',
            'requested_at' => now(),
            'refund_amount' => 25,
        ]);
    }
}
