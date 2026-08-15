<?php

namespace Tests\Feature;

use App\Domains\Cart\Services\CartService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PendingOrder;
use App\Models\PendingOrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PendingOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->customer = Customer::factory()->create(['status' => true]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'is_default' => true,
            'is_approved' => true,
            'status' => true,
        ]);
    }

    public function test_first_add_creates_one_active_pending_reference_with_default_hold_and_revision(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));

        $pending = PendingOrder::query()->firstOrFail();
        $cart = Cart::query()->firstOrFail();

        $this->assertSame(PendingOrder::STATUS_ACTIVE, $pending->status);
        $this->assertMatchesRegularExpression('/^PND-\d{4}-\d{6}$/', $pending->reference);
        $this->assertTrue($pending->expires_at->equalTo($pending->started_at->copy()->addMinutes(60)));
        $this->assertSame(1, PendingOrder::query()->active()->count());
        $this->assertSame(2, $cart->revision);

        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));

        $this->assertSame(1, PendingOrder::query()->active()->count());
        $this->assertSame(0, PendingOrderItem::query()->count());
        $this->assertSame(2.0, (float) CartItem::query()->firstOrFail()->fresh()->quantity);
    }

    public function test_custom_hold_and_reminder_are_respected_and_reminder_is_sent_once(): void
    {
        app(BusinessSettingService::class)->set('checkout.cart_hold_minutes', 60)->update(['value_type' => 'integer']);
        app(BusinessSettingService::class)->set('checkout.cart_reminder_minutes', 30)->update(['value_type' => 'integer']);
        [, $variant] = $this->purchasableVariant();

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $pending = PendingOrder::query()->firstOrFail();

        $this->assertTrue($pending->expires_at->equalTo($pending->started_at->copy()->addMinutes(60)));

        $this->travel(31)->minutes();
        $this->asCustomer()->get(route('cart.show'))->assertOk();
        $this->asCustomer()->get(route('cart.show'))->assertOk();

        $this->assertNotNull($pending->fresh()->reminder_sent_at);
        $this->assertSame(1, Notification::query()->where('type', 'pending_cart.reminder')->where('audience', 'customer')->count());
        $this->travelBack();
    }

    public function test_anchor_item_removal_keeps_one_activity_reference_and_tracks_anchor_change(): void
    {
        [, $anchorVariant] = $this->purchasableVariant(['name' => 'Anchor Rice']);
        [, $secondVariant] = $this->purchasableVariant(['name' => 'Second Dal']);

        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $anchorVariant->id, 'quantity' => 1]);
        $firstReference = PendingOrder::query()->firstOrFail()->reference;
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $secondVariant->id, 'quantity' => 1]);

        $anchorItem = CartItem::query()->where('product_variant_id', $anchorVariant->id)->firstOrFail();
        $this->asCustomer()->delete(route('cart.items.destroy', $anchorItem))->assertRedirect(route('cart.show'));

        $this->assertDatabaseHas('pending_orders', [
            'reference' => $firstReference,
            'status' => PendingOrder::STATUS_ACTIVE,
        ]);
        $this->assertSame(1, PendingOrder::query()->active()->count());
        $activity = PendingOrder::query()->active()->firstOrFail();
        $this->assertSame($firstReference, $activity->reference);
        $this->assertSame(1, $activity->anchor_change_count);
        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_expired_pending_cart_is_marked_not_ordered_and_cart_is_cleared(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->travel(61)->minutes();

        $this->asCustomer()->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Your cart expired because it was not ordered within the allowed time.');

        $pending = PendingOrder::query()->firstOrFail();
        $this->assertSame(PendingOrder::STATUS_NOT_ORDERED, $pending->status);
        $this->assertSame(PendingOrder::CLOSE_EXPIRED, $pending->close_reason);
        $this->assertSame(0, CartItem::query()->count());
        $this->assertSame(0, PendingOrderItem::query()->count());
        $this->travelBack();
    }

    public function test_status_endpoint_reports_shared_customer_cart_revision_and_counts(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->withSession(['customer_id' => $this->customer->id, 'cart_session_id' => 'device-b'])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $this->customer->id, 'cart_session_id' => 'device-c'])
            ->getJson(route('cart.status'))
            ->assertOk()
            ->assertJsonPath('item_count', 1)
            ->assertJsonPath('revision', Cart::query()->firstOrFail()->revision);

        $this->assertSame(1, Cart::query()->where('customer_id', $this->customer->id)->active()->count());
    }

    public function test_soft_deleted_active_cart_does_not_block_new_live_customer_cart(): void
    {
        $oldCart = Cart::factory()->create([
            'customer_id' => $this->customer->id,
            'session_id' => 'old-device',
            'status' => 'active',
        ]);
        $oldCart->delete();

        $cart = app(CartService::class)->getOrCreateCartForSession('customer:'.$this->customer->id);

        $this->assertNull($cart->deleted_at);
        $this->assertSame($this->customer->id, $cart->customer_id);
        $this->assertSame(1, Cart::query()
            ->where('customer_id', $this->customer->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->count());
    }

    public function test_successful_checkout_converts_pending_once_and_deducts_inventory_once(): void
    {
        [, $variant] = $this->purchasableVariant(inventoryQuantity: 1);

        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $pending = PendingOrder::query()->firstOrFail();

        $this->asCustomer()->post(route('checkout.place'), $this->checkoutPayload())
            ->assertRedirect();

        $this->assertSame(PendingOrder::STATUS_CONVERTED, $pending->fresh()->status);
        $this->assertNotNull($pending->fresh()->converted_order_id);
        $this->assertSame(1, Order::query()->count());
        $this->assertSame('0.000', Inventory::query()->where('product_variant_id', $variant->id)->firstOrFail()->quantity_on_hand);
        $this->assertSame(1, InventoryMovement::query()->where('movement_type', 'sale')->count());

        $this->asCustomer()->post(route('checkout.place'), $this->checkoutPayload())
            ->assertSessionHasErrors('checkout');

        $this->assertSame(1, Order::query()->count());
        $this->assertSame(1, InventoryMovement::query()->where('movement_type', 'sale')->count());
    }

    public function test_admin_pending_orders_list_and_detail_render_history(): void
    {
        [, $variant] = $this->purchasableVariant(['name' => 'Pending Sugar']);
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $pending = PendingOrder::query()->firstOrFail();
        $legacyCustomer = Customer::factory()->create();
        $legacyCart = Cart::factory()->create([
            'customer_id' => $legacyCustomer->id,
            'status' => 'active',
        ]);
        PendingOrder::query()->create([
            'customer_id' => $legacyCustomer->id,
            'cart_id' => $legacyCart->id,
            'reference' => 'PND-2026-999999',
            'status' => PendingOrder::STATUS_ACTIVE,
            'started_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pending-orders.index'))
            ->assertOk()
            ->assertSee('Cart Activity Monitor')
            ->assertSee($pending->reference)
            ->assertSee($this->customer->mobile)
            ->assertDontSee('PND-2026-999999')
            ->assertDontSee('Pending Sugar');

        $this->actingAs($this->admin)
            ->get(route('admin.pending-orders.show', $pending))
            ->assertOk()
            ->assertSee('Current Cart Items')
            ->assertSee('Pending Sugar');
    }

    public function test_pending_order_processor_is_idempotent_for_reminders_and_expiry(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->travel(31)->minutes();
        Artisan::call('pending-orders:process');
        Artisan::call('pending-orders:process');
        $this->assertSame(1, Notification::query()->where('type', 'pending_cart.reminder')->where('audience', 'customer')->count());

        $this->travel(30)->minutes();
        Artisan::call('pending-orders:process');
        Artisan::call('pending-orders:process');

        $pending = PendingOrder::query()->firstOrFail();
        $this->assertSame(PendingOrder::STATUS_NOT_ORDERED, $pending->status);
        $this->assertSame(PendingOrder::CLOSE_EXPIRED, $pending->close_reason);
        $this->assertSame(1, Notification::query()->where('type', 'pending_cart.expired')->where('audience', 'customer')->count());
        $this->travelBack();
    }

    public function test_processor_does_not_send_reminder_before_due_or_after_conversion(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        Artisan::call('pending-orders:process');
        $this->assertSame(0, Notification::query()->where('type', 'pending_cart.reminder')->count());

        PendingOrder::query()->firstOrFail()->update([
            'status' => PendingOrder::STATUS_CONVERTED,
            'closed_at' => now(),
        ]);

        $this->travel(31)->minutes();
        Artisan::call('pending-orders:process');
        $this->assertSame(0, Notification::query()->where('type', 'pending_cart.reminder')->count());
        $this->travelBack();
    }

    public function test_checkout_setting_validation_rejects_reminder_longer_than_hold(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.checkout.update'), $this->checkoutSettingsPayload([
                'cart_hold_minutes' => 30,
                'cart_reminder_minutes' => 45,
            ]))
            ->assertSessionHasErrors('cart_reminder_minutes');
    }

    public function test_checkout_setting_validation_rejects_invalid_increment_and_whatsapp_order(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.checkout.update'), $this->checkoutSettingsPayload([
                'cart_hold_minutes' => 60,
                'cart_reminder_minutes' => 20,
            ]))
            ->assertSessionHasErrors('cart_reminder_minutes');

        $this->actingAs($this->admin)
            ->put(route('admin.settings.checkout.update'), $this->checkoutSettingsPayload([
                'cart_hold_minutes' => 60,
                'cart_reminder_minutes' => 45,
                'cart_whatsapp_reminder_enabled' => 1,
                'cart_whatsapp_reminder_minutes' => 30,
            ]))
            ->assertSessionHasErrors('cart_whatsapp_reminder_minutes');
    }

    public function test_whatsapp_enabled_without_provider_skips_safely_once(): void
    {
        app(BusinessSettingService::class)->set('checkout.cart_whatsapp_reminder_enabled', true)->update(['value_type' => 'boolean']);
        app(BusinessSettingService::class)->set('checkout.cart_whatsapp_reminder_minutes', 45)->update(['value_type' => 'integer']);
        [, $variant] = $this->purchasableVariant();

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->travel(46)->minutes();
        Artisan::call('pending-orders:process');
        Artisan::call('pending-orders:process');

        $pending = PendingOrder::query()->firstOrFail();
        $this->assertSame('NOT_CONFIGURED', $pending->fresh()->whatsapp_reminder_status);
        $this->assertNotNull($pending->fresh()->whatsapp_reminder_attempted_at);
        $this->travelBack();
    }

    private function asCustomer()
    {
        return $this->withSession(['customer_id' => $this->customer->id]);
    }

    private function purchasableVariant(array $productOverrides = [], int $inventoryQuantity = 10): array
    {
        $product = Product::factory()->create(array_merge([
            'name' => 'Test Butter',
            'status' => true,
            'maximum_order_quantity' => 10,
        ], $productOverrides));
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'status' => true,
            'selling_price' => 120,
            'mrp' => 150,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => $inventoryQuantity,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        return [$product, $variant];
    }

    private function checkoutPayload(): array
    {
        $address = $this->customer->approvedAddresses()->firstOrFail();

        return [
            'customer_name' => $this->customer->name,
            'customer_mobile' => $this->customer->mobile,
            'customer_email' => $this->customer->email,
            'customer_address_id' => $address->id,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => null,
            'payment_method' => 'cod',
            'notes' => null,
        ];
    }

    private function checkoutSettingsPayload(array $overrides = []): array
    {
        return array_merge([
            'minimum_order_amount' => 0,
            'delivery_charge' => 0,
            'cod_enabled' => 1,
            'today_delivery_enabled' => 1,
            'today_delivery_cutoff_time' => '14:00',
            'custom_delivery_date_enabled' => 1,
            'max_delivery_days_ahead' => 7,
            'cart_hold_minutes' => 60,
            'cart_reminder_enabled' => 1,
            'cart_reminder_minutes' => 30,
            'cart_whatsapp_reminder_enabled' => 0,
            'cart_whatsapp_reminder_minutes' => 45,
            'cart_employee_followup_enabled' => 1,
            'cart_abuse_monitoring_enabled' => 1,
            'daily_offer_hold_minutes' => 15,
            'customer_credit_redemption_enabled' => 1,
            'return_window_days' => 2,
            'default_state' => null,
            'default_city' => null,
            'store_contact_mobile' => null,
            'store_whatsapp_number' => null,
            'customer_invoice_enabled' => 1,
        ], $overrides);
    }
}
