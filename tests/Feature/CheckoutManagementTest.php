<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CheckoutManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_checkout_page_loads_with_cart_and_redirects_when_empty(): void
    {
        $this->get(route('checkout.show'))
            ->assertRedirect(route('cart.show'));

        [, $variant] = $this->cartItem();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Cash on Delivery')
            ->assertSee('Place COD Order');
    }

    public function test_place_cod_order_from_cart_creates_snapshots_deducts_inventory_and_clears_cart(): void
    {
        [$product, $variant, $inventory] = $this->cartItem();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response = $this->post(route('checkout.place'), $this->checkoutPayload());

        $order = Order::query()->firstOrFail();
        $item = OrderItem::query()->firstOrFail();

        $response->assertRedirect(route('checkout.success', $order->order_number));
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame('placed', $order->order_status);
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame($product->name, $item->product_name_snapshot);
        $this->assertSame($variant->variant_name, $item->variant_name_snapshot);
        $this->assertSame($variant->sku, $item->sku_snapshot);
        $this->assertSame('136.00', $order->subtotal);
        $this->assertSame('150.00', $order->total_mrp);
        $this->assertSame('14.00', $order->total_savings);
        $this->assertSame('8.000', $inventory->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'movement_type' => 'sale',
            'quantity' => 2,
        ]);
        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_insufficient_stock_blocks_order_and_keeps_cart(): void
    {
        [, $variant, $inventory] = $this->cartItem(inventoryOverrides: ['quantity_on_hand' => 1]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $inventory->update(['quantity_on_hand' => 0]);

        $this->post(route('checkout.place'), $this->checkoutPayload())
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_order_success_page_is_session_protected(): void
    {
        [, $variant] = $this->cartItem();

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.place'), $this->checkoutPayload());

        $order = Order::query()->firstOrFail();

        $this->get(route('checkout.success', $order->order_number))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->withSession(['cart_session_id' => 'another-session'])
            ->get(route('checkout.success', $order->order_number))
            ->assertNotFound();
    }

    public function test_admin_order_index_show_update_and_cancellation_restores_stock(): void
    {
        [, $variant, $inventory] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);
        $this->post(route('checkout.place'), $this->checkoutPayload());
        $order = Order::query()->firstOrFail();

        $this->actingAs($this->admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number);

        $this->actingAs($this->admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Status History');

        $this->actingAs($this->admin)->patch(route('admin.orders.update-status', $order), [
            'order_status' => 'confirmed',
            'admin_notes' => 'Confirmed by admin',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('confirmed', $order->fresh()->order_status);

        $this->actingAs($this->admin)->patch(route('admin.orders.update-status', $order), [
            'order_status' => 'cancelled',
            'admin_notes' => 'Customer requested cancellation',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('cancelled', $order->fresh()->order_status);
        $this->assertSame('10.000', $inventory->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'movement_type' => 'cancellation_return',
            'quantity' => 2,
        ]);
    }

    public function test_no_disallowed_modules_or_catalog_stock_fields_are_created(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertNotContains('razorpay', $uris);
        $this->assertNotContains('cashback', $uris);
        $this->assertNotContains('coupons', $uris);

        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }

    private function cartItem(array $productOverrides = [], array $variantOverrides = [], array $inventoryOverrides = []): array
    {
        $product = Product::factory()->create(array_merge([
            'name' => 'Wheat Atta',
            'status' => true,
            'hsn_code' => '1101',
            'gst_rate' => 5,
        ], $productOverrides));
        $variant = ProductVariant::factory()->default()->create(array_merge([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'sku' => fake()->unique()->bothify('GK-ATTA-####'),
            'mrp' => 75,
            'selling_price' => 68,
            'status' => true,
        ], $variantOverrides));
        $product->update(['default_variant_id' => $variant->id]);
        $inventory = Inventory::factory()->create(array_merge([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ], $inventoryOverrides));

        return [$product, $variant, $inventory];
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'customer_email' => 'rohit@example.com',
            'delivery_address_line1' => 'House 12, Main Road',
            'delivery_address_line2' => 'Near Market',
            'delivery_city' => 'Patna',
            'delivery_state' => 'Bihar',
            'delivery_pincode' => '800001',
            'delivery_landmark' => 'Clock Tower',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => '9-11 AM',
            'notes' => 'Please call before delivery.',
        ];
    }
}
