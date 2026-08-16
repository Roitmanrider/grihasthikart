<?php

namespace Tests\Feature;

use App\Domains\Cart\Services\CartActivityRiskService;
use App\Domains\Messaging\Contracts\WhatsAppMessageResult;
use App\Domains\Messaging\Contracts\WhatsAppMessagingServiceInterface;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerCartRiskMonthly;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\PendingOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CartActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->customer = Customer::factory()->create(['status' => true]);
        CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'is_default' => true,
            'is_approved' => true,
            'status' => true,
        ]);
    }

    public function test_runtime_defaults_do_not_require_business_setting_seeder(): void
    {
        $settings = app(BusinessSettingService::class)->checkoutSettings();

        $this->assertSame(60, $settings['cart_hold_minutes']);
        $this->assertTrue($settings['cart_reminder_enabled']);
        $this->assertSame(30, $settings['cart_reminder_minutes']);
        $this->assertFalse($settings['cart_whatsapp_reminder_enabled']);
        $this->assertSame(45, $settings['cart_whatsapp_reminder_minutes']);
        $this->assertTrue($settings['cart_employee_followup_enabled']);
        $this->assertTrue($settings['cart_abuse_monitoring_enabled']);
        $this->assertSame(15, $settings['daily_offer_hold_minutes']);
    }

    public function test_daily_offer_reservation_default_is_15_minutes(): void
    {
        [, $variant] = $this->purchasableVariant();
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 5,
            'badge_text' => null,
        ]);

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'daily_offer_id' => $offer->id,
        ])->assertRedirect(route('cart.show'));

        $cart = Cart::query()->firstOrFail();
        $cartItem = CartItem::query()->firstOrFail();
        $activity = PendingOrder::query()->firstOrFail();

        $this->assertNull($cart->expires_at);
        $this->assertTrue($cartItem->daily_offer_reserved_until->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertTrue($activity->expires_at->between(now()->addMinutes(59), now()->addMinutes(61)));

        $this->travel(16)->minutes();
        $this->assertSame(5.0, $offer->fresh(['cartItems.cart', 'orderItems'])->availableOfferQuantity());
        $this->travelBack();
    }

    public function test_mixed_cart_keeps_normal_activity_hold_but_daily_offer_expires_after_15_minutes(): void
    {
        [, $normalVariant] = $this->purchasableVariant(['name' => 'Normal Rice']);
        [, $offerVariant] = $this->purchasableVariant(['name' => 'Offer Oil']);
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $offerVariant->id,
            'offer_price' => 90,
            'allocated_quantity' => 5,
            'badge_text' => null,
        ]);

        $this->travelTo(Carbon::now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $normalVariant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));
        $activity = PendingOrder::query()->firstOrFail();

        $this->assertNull(Cart::query()->firstOrFail()->expires_at);
        $this->assertTrue($activity->expires_at->between(now()->addMinutes(59), now()->addMinutes(61)));

        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $offerVariant->id,
            'quantity' => 1,
            'daily_offer_id' => $offer->id,
        ])->assertRedirect(route('cart.show'));

        $cart = Cart::query()->with('items')->firstOrFail();
        $offerItem = CartItem::query()->where('product_variant_id', $offerVariant->id)->firstOrFail();
        $this->assertNull($cart->expires_at);
        $this->assertTrue($offerItem->daily_offer_reserved_until->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertTrue($activity->fresh()->expires_at->between(now()->addMinutes(59), now()->addMinutes(61)));
        $this->assertSame(2, $cart->items->count());
        $this->assertSame(4.0, $offer->fresh(['cartItems.cart', 'orderItems'])->availableOfferQuantity());

        $this->travel(16)->minutes();
        $this->assertSame(5.0, $offer->fresh(['cartItems.cart', 'orderItems'])->availableOfferQuantity());
        $this->assertSame(2, Cart::query()->firstOrFail()->items()->count());
        $this->assertSame(PendingOrder::STATUS_ACTIVE, $activity->fresh()->status);
        $this->travelBack();
    }

    public function test_whatsapp_provider_send_is_recorded_once_when_configured(): void
    {
        app(BusinessSettingService::class)->set('checkout.cart_whatsapp_reminder_enabled', true)->update(['value_type' => 'boolean']);
        app(BusinessSettingService::class)->set('checkout.cart_whatsapp_reminder_minutes', 45)->update(['value_type' => 'integer']);
        $fake = new class implements WhatsAppMessagingServiceInterface
        {
            public int $sent = 0;

            public function configured(): bool
            {
                return true;
            }

            public function sendCartReminder(PendingOrder $pendingOrder, int $remainingMinutes): WhatsAppMessageResult
            {
                $this->sent++;

                return WhatsAppMessageResult::sent('wamid.test');
            }
        };
        $this->app->instance(WhatsAppMessagingServiceInterface::class, $fake);
        [, $variant] = $this->purchasableVariant();

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->travel(46)->minutes();
        Artisan::call('pending-orders:process');
        Artisan::call('pending-orders:process');

        $pending = PendingOrder::query()->firstOrFail();
        $this->assertSame(1, $fake->sent);
        $this->assertSame('SENT', $pending->fresh()->whatsapp_reminder_status);
        $this->assertSame('wamid.test', $pending->fresh()->whatsapp_provider_message_id);
        $this->travelBack();
    }

    public function test_monthly_risk_generation_is_idempotent_and_retains_six_months(): void
    {
        $cart = Cart::factory()->create(['customer_id' => $this->customer->id, 'status' => 'active']);
        PendingOrder::query()->create([
            'customer_id' => $this->customer->id,
            'cart_id' => $cart->id,
            'reference' => 'PND-2026-000001',
            'status' => PendingOrder::STATUS_NOT_ORDERED,
            'started_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay(),
            'last_activity_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay(),
            'expires_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay()->addHour(),
            'closed_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay()->addHour(),
            'close_reason' => PendingOrder::CLOSE_EXPIRED,
            'scarce_stock_hold' => true,
            'anchor_change_count' => 2,
        ]);

        $service = app(CartActivityRiskService::class);
        $service->generateForPreviousMonth();
        $firstGeneratedAt = CustomerCartRiskMonthly::query()->firstOrFail()->generated_at;
        $service->generateForPreviousMonth();

        $this->assertSame(1, CustomerCartRiskMonthly::query()->count());
        $risk = CustomerCartRiskMonthly::query()->firstOrFail();
        $this->assertSame('WATCH', $risk->risk_level);
        $this->assertSame(1, $risk->expired_count);
        $this->assertNotNull($risk->generated_at);
        $this->assertTrue($risk->generated_at->greaterThanOrEqualTo($firstGeneratedAt));

        CustomerCartRiskMonthly::query()->create([
            'customer_id' => $this->customer->id,
            'period_month' => now()->subMonthsNoOverflow(7)->startOfMonth(),
            'risk_level' => 'NORMAL',
        ]);

        $this->assertSame(1, $service->purgeOldRiskMarks());
    }

    public function test_cleanup_purges_only_eligible_cart_activity_not_orders(): void
    {
        $cart = Cart::factory()->create(['customer_id' => $this->customer->id, 'status' => 'active']);
        PendingOrder::query()->create([
            'customer_id' => $this->customer->id,
            'cart_id' => $cart->id,
            'reference' => 'PND-2026-000002',
            'status' => PendingOrder::STATUS_NOT_ORDERED,
            'started_at' => now()->subMonth(),
            'last_activity_at' => now()->subMonth(),
            'expires_at' => now()->subMonth()->addHour(),
            'closed_at' => now()->subMonth()->addHour(),
            'close_reason' => PendingOrder::CLOSE_EXPIRED,
            'monthly_risk_generated_at' => now()->subDays(8),
            'detail_cleanup_eligible_at' => now()->subDay(),
        ]);

        Artisan::call('cart-activity:cleanup');

        $this->assertSame(0, PendingOrder::query()->count());
        $this->assertSame(1, Cart::query()->count());
    }

    public function test_admin_cart_activity_monitor_renders_followup_terms(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        [, $variant] = $this->purchasableVariant(['name' => 'Monitor Rice']);

        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->actingAs($admin)
            ->get(route('admin.pending-orders.index'))
            ->assertOk()
            ->assertSee('Cart Activity Monitor')
            ->assertSee('Cart Follow-up')
            ->assertSee($this->customer->mobile);
    }

    public function test_admin_cart_activity_defaults_to_most_recent_activity_first(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $olderCart = Cart::factory()->create(['customer_id' => $this->customer->id, 'status' => 'active']);
        $newerCustomer = Customer::factory()->create(['name' => 'Newest Cart Customer']);
        $newerCart = Cart::factory()->create(['customer_id' => $newerCustomer->id, 'status' => 'active']);
        PendingOrder::query()->create([
            'customer_id' => $this->customer->id,
            'cart_id' => $olderCart->id,
            'reference' => 'PND-2026-000101',
            'status' => PendingOrder::STATUS_ACTIVE,
            'started_at' => now()->subHours(2),
            'last_activity_at' => now()->subHours(2),
            'expires_at' => now()->addHour(),
        ]);
        PendingOrder::query()->create([
            'customer_id' => $newerCustomer->id,
            'cart_id' => $newerCart->id,
            'reference' => 'PND-2026-000202',
            'status' => PendingOrder::STATUS_ACTIVE,
            'started_at' => now()->subHour(),
            'last_activity_at' => now()->subMinute(),
            'expires_at' => now()->addMinutes(10),
        ]);

        $content = $this->actingAs($admin)
            ->get(route('admin.pending-orders.index'))
            ->assertOk()
            ->assertSee('Most Recently Active')
            ->getContent();

        $this->assertLessThan(strpos($content, 'PND-2026-000101'), strpos($content, 'PND-2026-000202'));
    }

    public function test_cart_activity_reserves_inventory_and_releases_on_remove_and_expiry(): void
    {
        [, $variant] = $this->purchasableVariant(inventoryQuantity: 5);

        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertRedirect(route('cart.show'));

        $inventory = Inventory::query()->where('product_variant_id', $variant->id)->firstOrFail();
        $this->assertSame('2.000', $inventory->fresh()->reserved_quantity);
        $this->assertSame(3.0, $inventory->fresh()->available_quantity);

        $item = CartItem::query()->firstOrFail();
        $this->asCustomer()->delete(route('cart.items.destroy', $item))->assertRedirect(route('cart.show'));
        $this->assertSame('0.000', $inventory->fresh()->reserved_quantity);

        $this->asCustomer()->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));
        $pending = PendingOrder::query()->active()->firstOrFail();
        $pending->update(['expires_at' => now()->subMinute()]);

        Artisan::call('pending-orders:process');

        $this->assertSame('0.000', $inventory->fresh()->reserved_quantity);
        $this->assertSame(PendingOrder::STATUS_NOT_ORDERED, $pending->fresh()->status);
    }

    public function test_followup_queue_uses_in_app_reminder_stage_and_assignment_without_whatsapp(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $employee = User::factory()->create(['email' => 'employee@example.com']);
        $this->customer->update(['is_premium' => true]);
        [, $variant] = $this->purchasableVariant(['name' => 'Followup Rice']);

        $this->travelTo(now()->setSecond(0));
        $this->asCustomer()->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $pending = PendingOrder::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.pending-orders.index', ['filters' => ['call_followup']]))
            ->assertOk()
            ->assertDontSee($pending->reference);

        $this->travel(31)->minutes();
        Artisan::call('pending-orders:process');
        $pending->refresh();
        $this->assertNotNull($pending->follow_up_eligible_at);
        $this->assertNull($pending->whatsapp_reminder_attempted_at);

        $this->actingAs($admin)
            ->get(route('admin.pending-orders.index', [
                'filters' => ['call_followup', 'premium', 'not_contacted'],
                'sort' => 'oldest_waiting',
            ]))
            ->assertOk()
            ->assertSee($pending->reference)
            ->assertSee('Need Follow-up')
            ->assertSee('Assigned to Me')
            ->assertSee('Not Contacted');

        $this->actingAs($admin)
            ->patch(route('admin.pending-orders.assign', $pending), ['assigned_admin_user_id' => $employee->id])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.pending-orders.index', [
                'filters' => ['call_followup'],
                'assigned_admin_user_id' => $employee->id,
            ]))
            ->assertOk()
            ->assertSee($pending->reference);

        $this->actingAs($admin)
            ->patch(route('admin.pending-orders.follow-up', $pending), ['follow_up_status' => 'CALLED'])
            ->assertRedirect();

        $this->assertSame('CALLED', $pending->fresh()->follow_up_status);
        $this->assertNotNull($pending->fresh()->follow_up_updated_at);

        PendingOrder::query()->create([
            'customer_id' => Customer::factory()->create()->id,
            'cart_id' => Cart::factory()->create(['status' => 'active'])->id,
            'reference' => 'PND-2026-888888',
            'status' => PendingOrder::STATUS_ACTIVE,
            'started_at' => now()->subHours(2),
            'follow_up_eligible_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.pending-orders.index', ['filters' => ['call_followup']]))
            ->assertOk()
            ->assertDontSee('PND-2026-888888');

        $this->travelBack();
    }

    private function asCustomer()
    {
        return $this->withSession(['customer_id' => $this->customer->id]);
    }

    private function purchasableVariant(array $productOverrides = [], int $inventoryQuantity = 10): array
    {
        $product = Product::factory()->create(array_merge([
            'name' => 'Activity Butter',
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
}
