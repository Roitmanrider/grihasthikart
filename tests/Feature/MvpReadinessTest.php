<?php

namespace Tests\Feature;

use App\Models\CashbackRedemptionRequest;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MvpReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
    }

    public function test_customer_home_cart_checkout_and_dashboard_routes_load(): void
    {
        $customer = Customer::factory()->create(['mobile' => '9876543210']);

        $this->get(route('home'))->assertOk()->assertSee('GrihasthiKart');
        $this->get(route('cart.show'))->assertOk()->assertSee('Cart');

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('My Account')
            ->assertSee('Cashback');
    }

    public function test_admin_dashboard_loads_with_summary_cards(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        Product::factory()->create();
        Order::factory()->create(['order_status' => 'placed']);
        Payment::factory()->create(['payment_status' => 'pending']);
        CashbackRedemptionRequest::factory()->create(['status' => 'pending']);
        $variant = ProductVariant::factory()->create();
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 1,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'low_stock_threshold' => 5,
        ]);

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Admin Dashboard')
            ->assertSee('Pending Orders')
            ->assertSee('Low Stock Items')
            ->assertSee('Cashback Requests');
    }

    public function test_key_admin_navigation_routes_load_for_admin(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        foreach ([
            route('admin.products.index'),
            route('admin.orders.index'),
            route('admin.payments.index'),
            route('admin.coupons.index'),
            route('admin.cashback.index'),
            route('admin.reports.gst-summary'),
            route('admin.settings.checkout.edit'),
        ] as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }
    }

    public function test_no_accidental_modules_or_catalog_stock_fields_are_created(): void
    {
        $this->assertFalse(class_exists('App\\Models\\Wishlist'));
        $this->assertFalse(class_exists('App\\Models\\Wallet'));
        $this->assertFalse(class_exists('App\\Models\\InvoicePdf'));

        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }
}
