<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\CartItem;
use App\Models\DeliverySlot;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BusinessSettingSeeder;
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
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9 AM - 11 AM',
            'status' => true,
        ]);
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

        $this->get(route('checkout.show', ['delivery_date' => now(config('app.timezone'))->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('Cash on Delivery')
            ->assertSee('9 AM - 11 AM')
            ->assertSee('Place Order');
    }

    public function test_inactive_delivery_slots_do_not_appear_on_checkout_page(): void
    {
        DeliverySlot::factory()->inactive()->create([
            'name' => 'Midnight',
            'start_time' => '00:00',
            'end_time' => '01:00',
            'display_label' => 'Midnight',
        ]);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('checkout.show', ['delivery_date' => now(config('app.timezone'))->addDay()->toDateString()]))
            ->assertOk()
            ->assertSee('9 AM - 11 AM')
            ->assertDontSee('Midnight');
    }

    public function test_checkout_filters_and_rejects_expired_same_day_delivery_slots(): void
    {
        $this->travelTo(now(config('app.timezone'))->setTime(13, 0));
        DeliverySlot::query()->delete();
        DeliverySlot::factory()->create([
            'name' => '7-9 AM',
            'start_time' => '07:00',
            'end_time' => '09:00',
            'display_label' => '7 AM - 9 AM',
            'status' => true,
        ]);
        DeliverySlot::factory()->create([
            'name' => '4-6 PM',
            'start_time' => '16:00',
            'end_time' => '18:00',
            'display_label' => '4 PM - 6 PM',
            'status' => true,
        ]);
        DeliverySlot::factory()->create([
            'name' => '6-8 PM',
            'start_time' => '18:00',
            'end_time' => '20:00',
            'display_label' => '6 PM - 8 PM',
            'status' => true,
        ]);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('checkout.show', ['delivery_date' => now(config('app.timezone'))->toDateString()]))
            ->assertOk()
            ->assertDontSee('7 AM - 9 AM')
            ->assertSee('4 PM - 6 PM')
            ->assertSee('6 PM - 8 PM');

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'delivery_date' => now(config('app.timezone'))->toDateString(),
            'delivery_slot' => '7 AM - 9 AM',
        ]))->assertSessionHasErrors('checkout');

        $this->travelBack();
    }

    public function test_same_day_checkout_at_10_am_shows_only_approved_future_evening_slots(): void
    {
        $this->travelTo(Carbon::parse('2026-07-06 10:00:00', config('app.timezone')));
        DeliverySlot::query()->delete();
        $this->seedStandardDeliverySlots();
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee(now(config('app.timezone'))->toDateString())
            ->assertDontSee('7 AM - 9 AM')
            ->assertDontSee('9 AM - 11 AM')
            ->assertSee('4 PM - 6 PM')
            ->assertSee('6 PM - 8 PM');

        $this->travelBack();
    }

    public function test_checkout_after_same_day_cutoff_defaults_to_tomorrow_and_rejects_today(): void
    {
        $this->travelTo(Carbon::parse('2026-07-06 21:00:00', config('app.timezone')));
        DeliverySlot::query()->delete();
        $this->seedStandardDeliverySlots();
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $today = now(config('app.timezone'))->toDateString();
        $tomorrow = now(config('app.timezone'))->addDay()->toDateString();

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('value="'.$tomorrow.'"', false)
            ->assertSee('min="'.$tomorrow.'"', false)
            ->assertSee('7 AM - 9 AM')
            ->assertSee('9 AM - 11 AM')
            ->assertSee('4 PM - 6 PM')
            ->assertSee('6 PM - 8 PM');

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'delivery_date' => $today,
            'delivery_slot' => '4 PM - 6 PM',
        ]))->assertSessionHasErrors('checkout');

        $this->travelBack();
    }

    public function test_checkout_before_same_day_window_defaults_to_tomorrow(): void
    {
        $this->travelTo(Carbon::parse('2026-07-06 04:00:00', config('app.timezone')));
        DeliverySlot::query()->delete();
        $this->seedStandardDeliverySlots();
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $tomorrow = now(config('app.timezone'))->addDay()->toDateString();

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('value="'.$tomorrow.'"', false)
            ->assertSee('min="'.$tomorrow.'"', false);

        $this->travelBack();
    }

    public function test_future_delivery_date_allows_active_slots(): void
    {
        $this->travelTo(now(config('app.timezone'))->setTime(13, 0));
        DeliverySlot::query()->delete();
        DeliverySlot::factory()->create([
            'name' => '7-9 AM',
            'start_time' => '07:00',
            'end_time' => '09:00',
            'display_label' => '7 AM - 9 AM',
            'status' => true,
        ]);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $futureDate = now(config('app.timezone'))->addDay()->toDateString();

        $this->get(route('checkout.show', ['delivery_date' => $futureDate]))
            ->assertOk()
            ->assertSee('7 AM - 9 AM');

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'delivery_date' => $futureDate,
            'delivery_slot' => '7 AM - 9 AM',
        ]))->assertRedirect();

        $this->travelBack();
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
        $this->assertSame('pending', $order->payment_status);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'amount' => '136',
        ]);
        $this->assertSame('placed', $order->order_status);
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame($product->name, $item->product_name_snapshot);
        $this->assertSame($variant->variant_name, $item->variant_name_snapshot);
        $this->assertSame($variant->sku, $item->sku_snapshot);
        $this->assertSame('136.00', $order->subtotal);
        $this->assertSame('150.00', $order->total_mrp);
        $this->assertSame('14.00', $order->total_savings);
        $this->assertSame('9 AM - 11 AM', $order->delivery_slot);
        $this->assertSame('8.000', $inventory->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'movement_type' => 'sale',
            'quantity' => 2,
        ]);
        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_checkout_blocks_cod_when_disabled_and_enforces_minimum_order_amount(): void
    {
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        BusinessSetting::query()->where('group', 'payment')->where('key', 'cod_enabled')->update(['value' => '0']);
        $this->post(route('checkout.place'), $this->checkoutPayload())->assertSessionHasErrors('checkout');

        BusinessSetting::query()->where('group', 'payment')->where('key', 'cod_enabled')->update(['value' => '1']);
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'minimum_order_amount')->update(['value' => '999']);
        $this->post(route('checkout.place'), $this->checkoutPayload())->assertSessionHasErrors('checkout');
    }

    public function test_checkout_validation_errors_render_once_near_fields(): void
    {
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $response = $this->followingRedirects()
            ->from(route('checkout.show'))
            ->post(route('checkout.place'), [])
            ->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'The customer name field is required.'));
        $this->assertSame(1, substr_count($response->getContent(), 'The customer mobile field is required.'));
    }

    public function test_qr_checkout_creates_pending_payment_record(): void
    {
        BusinessSetting::query()->where('group', 'payment')->where('key', 'qr_enabled')->update(['value' => '1']);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'payment_method' => 'qr',
        ]))->assertRedirect();

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->assertSame('qr', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame('qr', $payment->payment_method);
        $this->assertSame('pending', $payment->payment_status);
    }

    public function test_checkout_applies_delivery_charge_to_order_total(): void
    {
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'delivery_charge')->update(['value' => '25']);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('25.00', $order->delivery_charge);
        $this->assertSame('93.00', $order->grand_total);
    }

    public function test_checkout_blocks_today_after_cutoff_and_future_date_beyond_limit(): void
    {
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'today_delivery_cutoff_time')->update(['value' => '00:00']);
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'max_delivery_days_ahead')->update(['value' => '1']);
        [, $variant] = $this->cartItem();
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'delivery_date' => now()->toDateString(),
        ]))->assertSessionHasErrors('checkout');

        $this->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'delivery_date' => now()->addDays(2)->toDateString(),
        ]))->assertSessionHasErrors('checkout');
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

        $this->assertContains('orders/{orderNumber}/payment-proof', $uris);
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

    private function seedStandardDeliverySlots(): void
    {
        foreach ([
            ['7-9 AM', '07:00', '09:00', '7 AM - 9 AM', 1],
            ['9-11 AM', '09:00', '11:00', '9 AM - 11 AM', 2],
            ['4-6 PM', '16:00', '18:00', '4 PM - 6 PM', 3],
            ['6-8 PM', '18:00', '20:00', '6 PM - 8 PM', 4],
        ] as [$name, $start, $end, $label, $order]) {
            DeliverySlot::query()->create([
                'name' => $name,
                'start_time' => $start,
                'end_time' => $end,
                'display_label' => $label,
                'status' => true,
                'display_order' => $order,
            ]);
        }
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
            'delivery_slot' => '9 AM - 11 AM',
            'payment_method' => 'cod',
            'notes' => 'Please call before delivery.',
        ];
    }
}
