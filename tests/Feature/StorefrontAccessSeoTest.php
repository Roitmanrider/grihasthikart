<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class StorefrontAccessSeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        Cache::flush();
    }

    public function test_public_browse_members_buy_allows_catalog_browsing_but_blocks_guest_transactions(): void
    {
        $product = $this->product();

        $this->get(route('home'))->assertOk();
        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.show', $product->slug))->assertOk();
        $this->get(route('categories.index'))->assertOk();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $product->default_variant_id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.login'));

        $this->get(route('checkout.show'))->assertRedirect(route('customer.login'));
        $this->get(route('wishlist.index'))->assertRedirect(route('customer.login'));
    }

    public function test_members_only_storefront_blocks_guest_catalog_and_respects_homepage_exception(): void
    {
        $product = $this->product();
        $this->setStorefrontMode('MEMBERS_ONLY_STOREFRONT', true);

        $this->get(route('home'))->assertOk();
        $this->get(route('products.show', $product->slug))->assertRedirect(route('customer.login'));
        $this->get(route('categories.index'))->assertRedirect(route('customer.login'));
        $this->get(route('pages.privacy'))->assertOk();

        $customer = Customer::factory()->create(['status' => true]);
        $this->withSession(['customer_id' => $customer->id])
            ->get(route('products.show', $product->slug))
            ->assertOk();

        $this->flushSession();

        $this->setStorefrontMode('MEMBERS_ONLY_STOREFRONT', false);
        $this->get(route('home'))->assertRedirect(route('customer.login'));
    }

    public function test_seo_status_reports_public_and_members_only_visibility(): void
    {
        $service = app(StorefrontAccessService::class);

        $this->assertTrue($service->seoStatus()['product_public_indexing']);
        $this->assertTrue($service->seoStatus()['future_sitemap_inclusion']);
        $this->assertSame('Normal', $service->seoStatus()['google_ads_landing_suitability']);

        $this->setStorefrontMode('MEMBERS_ONLY_STOREFRONT', true);

        $status = $service->seoStatus();
        $this->assertFalse($status['product_public_indexing']);
        $this->assertFalse($status['category_public_indexing']);
        $this->assertFalse($status['future_sitemap_inclusion']);
        $this->assertTrue($status['protected_pages_noindex']);
        $this->assertSame('Limited - public landing pages only', $status['google_ads_landing_suitability']);
    }

    public function test_admin_members_only_mode_requires_seo_acknowledgement_and_admin_routes_remain_available(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $payload = $this->storefrontPayload([
            'access_mode' => 'MEMBERS_ONLY_STOREFRONT',
            'homepage_public_in_members_only' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.settings.storefront-seo.update'), $payload)
            ->assertSessionHasErrors('members_only_seo_acknowledged');

        $this->actingAs($admin)
            ->put(route('admin.settings.storefront-seo.update'), array_merge($payload, [
                'members_only_seo_acknowledged' => 1,
            ]))
            ->assertRedirect(route('admin.settings.storefront-seo.edit'));

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.settings.storefront-seo.edit'))
            ->assertOk()
            ->assertSeeText('SEO & Storefront Visibility')
            ->assertSee('Current Active Mode:')
            ->assertSee('Members-Only Storefront')
            ->assertSee('Future Sitemap Inclusion')
            ->assertSee('Google Ads Landing Suitability')
            ->assertSee('Limited - public landing pages only');
    }

    public function test_public_modes_do_not_require_members_only_acknowledgement_and_checkout_page_is_checkout_only(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.settings.storefront-seo.update'), $this->storefrontPayload([
                'access_mode' => 'PUBLIC_BROWSE_MEMBERS_BUY',
                'allow_guest_checkout' => 1,
            ]))
            ->assertRedirect(route('admin.settings.storefront-seo.edit'));

        $this->assertSame('PUBLIC_BROWSE_MEMBERS_BUY', app(StorefrontAccessService::class)->mode());

        $this->actingAs($admin)
            ->get(route('admin.settings.checkout.edit'))
            ->assertOk()
            ->assertDontSee('Storefront Access')
            ->assertDontSee('SEO & Storefront Visibility');
    }

    public function test_public_storefront_guest_checkout_policy_controls_guest_transactions(): void
    {
        $product = $this->product();
        $this->setStorefrontMode('PUBLIC_STOREFRONT', true, false);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $product->default_variant_id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.login'));

        $this->setStorefrontMode('PUBLIC_STOREFRONT', true, true);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $product->default_variant_id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));

        $this->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Full Name *')
            ->assertSee('Mobile Number *')
            ->assertSee('Address Line 1 *')
            ->assertSee('PIN Code *');
    }

    public function test_public_browse_members_buy_ignores_guest_checkout_switch(): void
    {
        $product = $this->product();
        $this->setStorefrontMode('PUBLIC_BROWSE_MEMBERS_BUY', true, true);

        $this->get(route('products.show', $product->slug))->assertOk();
        $this->post(route('cart.items.store'), [
            'product_variant_id' => $product->default_variant_id,
            'quantity' => 1,
        ])->assertRedirect(route('customer.login'));
    }

    public function test_missing_storefront_database_rows_use_safe_runtime_defaults(): void
    {
        BusinessSetting::query()->where('group', 'storefront')->delete();
        Cache::flush();

        $service = app(StorefrontAccessService::class);

        $this->assertSame('PUBLIC_BROWSE_MEMBERS_BUY', $service->mode());
        $this->assertTrue($service->homepagePublicInMembersOnly());
        $this->assertFalse($service->allowGuestCheckout());
    }

    public function test_storefront_admin_save_creates_missing_setting_rows(): void
    {
        BusinessSetting::query()->where('group', 'storefront')->delete();
        Cache::flush();
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)
            ->put(route('admin.settings.storefront-seo.update'), $this->storefrontPayload([
                'access_mode' => 'PUBLIC_STOREFRONT',
                'allow_guest_checkout' => 1,
                'guest_checkout_acknowledged' => 1,
            ]))
            ->assertRedirect(route('admin.settings.storefront-seo.edit'));

        $this->assertDatabaseHas('business_settings', [
            'group' => 'storefront',
            'key' => 'allow_guest_checkout',
            'value' => '1',
            'value_type' => 'boolean',
        ]);
    }

    public function test_mobile_navigation_contains_exact_customer_navigation_items(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Categories')
            ->assertSee('Orders')
            ->assertSee('Wishlist')
            ->assertSee('My Account')
            ->assertDontSee('Alerts');
    }

    private function product(): Product
    {
        $category = Category::factory()->create(['name' => 'Staples', 'slug' => 'staples', 'status' => true]);
        $product = Product::factory()->create(['name' => 'Test Rice', 'slug' => 'test-rice', 'status' => true]);
        $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'status' => true]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);
        $product->categories()->attach($category->id, ['is_primary' => true, 'display_order' => 0]);
        $product->update(['default_variant_id' => $variant->id]);

        return $product->fresh();
    }

    private function setStorefrontMode(string $mode, bool $homepagePublic, bool $allowGuestCheckout = false): void
    {
        $settings = app(BusinessSettingService::class);
        $settings->updateStorefrontSettings([
            'access_mode' => $mode,
            'homepage_public_in_members_only' => $homepagePublic,
            'allow_guest_checkout' => $allowGuestCheckout,
        ]);
        Cache::flush();
    }

    private function storefrontPayload(array $overrides = []): array
    {
        return array_merge([
            'access_mode' => 'PUBLIC_BROWSE_MEMBERS_BUY',
            'homepage_public_in_members_only' => 1,
            'allow_guest_checkout' => 0,
        ], $overrides);
    }
}
