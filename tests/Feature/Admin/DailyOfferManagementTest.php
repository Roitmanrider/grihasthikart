<?php

namespace Tests\Feature\Admin;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyOfferManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Asia/Kolkata']);
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_daily_offers_index_requires_admin_auth(): void
    {
        $this->get(route('admin.daily-offers.index'))
            ->assertRedirect(route('admin.login'));

        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)
            ->get(route('admin.daily-offers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_daily_offer(): void
    {
        $variant = $this->variant();

        $response = $this->actingAs($this->admin)->post(route('admin.daily-offers.store'), [
            'product_variant_id' => $variant->id,
            'title' => 'Banana Robusta Deal',
            'offer_price' => 30,
            'starts_at' => now()->subHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'is_active' => 1,
            'display_order' => 1,
            'max_quantity_per_order' => 3,
            'badge_text' => '25% OFF',
        ]);

        $response->assertRedirect(route('admin.daily-offers.index'));

        $this->assertDatabaseHas('daily_offers', [
            'product_variant_id' => $variant->id,
            'title' => 'Banana Robusta Deal',
            'offer_price' => 30,
            'is_active' => true,
            'max_quantity_per_order' => 3,
            'badge_text' => '25% OFF',
        ]);
    }

    public function test_admin_can_update_deactivate_and_delete_daily_offer(): void
    {
        $variant = $this->variant();
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 35,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.daily-offers.update', $offer), [
                'product_variant_id' => $variant->id,
                'title' => 'Updated Deal',
                'offer_price' => 28,
                'is_active' => 0,
                'display_order' => 4,
            ])
            ->assertRedirect(route('admin.daily-offers.index'));

        $this->assertDatabaseHas('daily_offers', [
            'id' => $offer->id,
            'title' => 'Updated Deal',
            'offer_price' => 28,
            'is_active' => false,
            'display_order' => 4,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.daily-offers.destroy', $offer))
            ->assertRedirect(route('admin.daily-offers.index'));

        $this->assertSoftDeleted('daily_offers', ['id' => $offer->id]);
    }

    public function test_invalid_offer_price_and_schedule_are_rejected(): void
    {
        $variant = $this->variant(['mrp' => 100, 'selling_price' => 90]);

        $this->actingAs($this->admin)
            ->from(route('admin.daily-offers.create'))
            ->post(route('admin.daily-offers.store'), [
                'product_variant_id' => $variant->id,
                'offer_price' => 101,
                'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'ends_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.daily-offers.create'))
            ->assertSessionHasErrors(['ends_at']);

        $this->actingAs($this->admin)
            ->from(route('admin.daily-offers.create'))
            ->post(route('admin.daily-offers.store'), [
                'product_variant_id' => $variant->id,
                'offer_price' => 101,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.daily-offers.create'))
            ->assertSessionHasErrors('daily_offer');
    }

    public function test_duplicate_active_daily_offer_for_same_variant_is_rejected(): void
    {
        $variant = $this->variant();

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->from(route('admin.daily-offers.create'))
            ->post(route('admin.daily-offers.store'), [
                'product_variant_id' => $variant->id,
                'offer_price' => 30,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.daily-offers.create'))
            ->assertSessionHasErrors('daily_offer');
    }

    public function test_current_active_daily_offers_appear_on_homepage(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:12:00', config('app.timezone')));
        $variant = $this->variant(['mrp' => 40, 'selling_price' => 35]);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'title' => 'Banana Robusta',
            'offer_price' => 30,
            'badge_text' => '25% OFF',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Banana Robusta')
            ->assertSee('Rs. 30')
            ->assertSee('25% OFF')
            ->assertDontSee(route('wishlist.items.store'), false);
    }

    public function test_daily_offer_visibility_uses_current_app_timezone_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:12:00', config('app.timezone')));
        $variant = $this->variant(['sku' => 'GK-LIVE-TIME']);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'title' => 'Live Time Offer',
            'offer_price' => 30,
            'starts_at' => Carbon::parse('2026-07-07 17:11:00', config('app.timezone')),
            'ends_at' => Carbon::parse('2026-07-07 17:15:00', config('app.timezone')),
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Live Time Offer');
    }

    public function test_daily_offer_does_not_appear_before_start_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:10:00', config('app.timezone')));
        $variant = $this->variant(['sku' => 'GK-SCHEDULED-TIME']);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'title' => 'Scheduled Time Offer',
            'offer_price' => 30,
            'starts_at' => Carbon::parse('2026-07-07 17:11:00', config('app.timezone')),
            'ends_at' => Carbon::parse('2026-07-07 17:15:00', config('app.timezone')),
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Scheduled Time Offer');
    }

    public function test_daily_offer_does_not_appear_after_end_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:16:00', config('app.timezone')));
        $variant = $this->variant(['sku' => 'GK-EXPIRED-TIME']);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'title' => 'Expired Time Offer',
            'offer_price' => 30,
            'starts_at' => Carbon::parse('2026-07-07 17:11:00', config('app.timezone')),
            'ends_at' => Carbon::parse('2026-07-07 17:15:00', config('app.timezone')),
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Expired Time Offer');
    }

    public function test_daily_offer_with_null_start_and_open_end_can_appear(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:12:00', config('app.timezone')));
        $variant = $this->variant(['sku' => 'GK-OPEN-TIME']);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'title' => 'Open Time Offer',
            'offer_price' => 30,
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Open Time Offer');
    }

    public function test_customer_daily_offers_page_lists_current_offers_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:12:00', config('app.timezone')));
        $liveVariant = $this->variant(['sku' => 'GK-DAILY-PAGE']);
        $expiredVariant = $this->variant(['sku' => 'GK-DAILY-PAGE-OLD']);

        DailyOffer::factory()->create([
            'product_variant_id' => $liveVariant->id,
            'title' => 'Daily Page Offer',
            'offer_price' => 30,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinute(),
            'is_active' => true,
        ]);
        DailyOffer::factory()->expired()->create([
            'product_variant_id' => $expiredVariant->id,
            'title' => 'Old Daily Page Offer',
        ]);

        $this->get(route('daily-offers.index'))
            ->assertOk()
            ->assertSee('Daily Page Offer')
            ->assertDontSee('Old Daily Page Offer')
            ->assertDontSee(route('wishlist.items.store'), false);
    }

    public function test_admin_daily_offers_index_shows_app_clock_and_lifecycle_badges(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 17:12:00', config('app.timezone')));
        $liveVariant = $this->variant(['sku' => 'GK-LIVE-BADGE']);
        $scheduledVariant = $this->variant(['sku' => 'GK-SCHEDULED-BADGE']);
        $expiredVariant = $this->variant(['sku' => 'GK-EXPIRED-BADGE']);
        $inactiveVariant = $this->variant(['sku' => 'GK-INACTIVE-BADGE']);

        DailyOffer::factory()->create([
            'product_variant_id' => $liveVariant->id,
            'title' => 'Live Badge Offer',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMinute(),
            'is_active' => true,
        ]);
        DailyOffer::factory()->create([
            'product_variant_id' => $scheduledVariant->id,
            'title' => 'Scheduled Badge Offer',
            'starts_at' => now()->addMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);
        DailyOffer::factory()->create([
            'product_variant_id' => $expiredVariant->id,
            'title' => 'Expired Badge Offer',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
            'is_active' => true,
        ]);
        DailyOffer::factory()->create([
            'product_variant_id' => $inactiveVariant->id,
            'title' => 'Inactive Badge Offer',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.daily-offers.index'))
            ->assertOk()
            ->assertSee('Current app time:')
            ->assertSee('07 Jul 2026, 05:12 PM')
            ->assertSee('Live Now')
            ->assertSee('Scheduled')
            ->assertSee('Expired')
            ->assertSee('Inactive');
    }

    public function test_expired_and_inactive_daily_offers_do_not_appear_on_homepage(): void
    {
        $expiredVariant = $this->variant(['sku' => 'GK-EXPIRED']);
        $inactiveVariant = $this->variant(['sku' => 'GK-INACTIVE-OFFER']);

        DailyOffer::factory()->expired()->create([
            'product_variant_id' => $expiredVariant->id,
            'title' => 'Expired Offer',
        ]);
        DailyOffer::factory()->inactive()->create([
            'product_variant_id' => $inactiveVariant->id,
            'title' => 'Inactive Offer',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Daily offers coming soon.')
            ->assertDontSee('Expired Offer')
            ->assertDontSee('Inactive Offer');
    }

    public function test_homepage_daily_offers_no_longer_depend_on_featured_products(): void
    {
        $product = Product::factory()->create([
            'name' => 'Featured Product Only',
            'slug' => 'featured-product-only',
            'is_featured' => true,
            'status' => true,
        ]);
        ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'sku' => 'GK-FEATURED-ONLY',
            'status' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Daily offers coming soon.')
            ->assertDontSee('Featured Product Only');
    }

    public function test_daily_offer_add_to_cart_uses_offer_price_and_sets_thirty_minute_hold(): void
    {
        $variant = $this->variant(['mrp' => 40, 'selling_price' => 35]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 20,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);
        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 30,
            'max_quantity_per_order' => 2,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));

        $item = CartItem::query()->firstOrFail();
        $cart = Cart::query()->firstOrFail();

        $this->assertSame('30.00', (string) $item->unit_price);
        $this->assertNotNull($cart->expires_at);
        $this->assertTrue($cart->expires_at->between(now()->addMinutes(29), now()->addMinutes(31)));
    }

    public function test_daily_offer_max_quantity_per_order_is_enforced_in_cart(): void
    {
        $variant = $this->variant(['mrp' => 40, 'selling_price' => 35]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 20,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);
        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 30,
            'max_quantity_per_order' => 1,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('cart');
    }

    private function variant(array $overrides = []): ProductVariant
    {
        $sku = $overrides['sku'] ?? 'GK-BANANA-'.fake()->unique()->numberBetween(1000, 9999);

        $product = Product::factory()->create([
            'name' => 'Banana Robusta '.$sku,
            'slug' => 'banana-robusta-'.strtolower($sku),
            'status' => true,
        ]);

        $variant = ProductVariant::factory()->create(array_merge([
            'product_id' => $product->id,
            'sku' => $sku,
            'variant_name' => '1 kg',
            'mrp' => 40,
            'selling_price' => 35,
            'status' => true,
        ], $overrides));

        $product->update(['default_variant_id' => $variant->id]);

        return $variant;
    }
}
