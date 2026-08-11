<?php

namespace Tests\Feature;

use App\Domains\Cart\Services\CartActivityRiskService;
use App\Domains\Messaging\Contracts\WhatsAppMessageResult;
use App\Domains\Messaging\Contracts\WhatsAppMessagingServiceInterface;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
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
        $activity = PendingOrder::query()->firstOrFail();

        $this->assertTrue($cart->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
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
        $this->assertTrue($cart->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
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
