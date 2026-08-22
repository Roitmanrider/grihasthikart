<?php

namespace Tests\Feature;

use App\Domains\Catalog\Services\DailyOfferService;
use App\Domains\Marketing\Services\CustomerAnnouncementService;
use App\Domains\Marketing\Services\CustomerMarketingBannerService;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerAnnouncement;
use App\Models\CustomerMarketingBanner;
use App\Models\DailyOffer;
use App\Models\DeliverySlot;
use App\Models\HomepageSection;
use App\Models\Inventory;
use App\Models\OrderItem;
use App\Models\PendingOrder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\StoreVariantPrice;
use App\Models\StoreVariantPriceHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiStoreOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_customer_cart_uses_store_price_and_reserves_assigned_store_stock(): void
    {
        [$storeA, $storeB, $customer] = $this->storesAndCustomer();
        [, $variant] = $this->variantWithInventory($storeA, $storeB);

        StoreVariantPrice::query()->create([
            'stock_location_id' => $storeA->id,
            'product_variant_id' => $variant->id,
            'mrp' => 150,
            'selling_price' => 99,
            'status' => true,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 2,
            ])
            ->assertRedirect(route('cart.show'));

        $cart = Cart::query()->with('items')->firstOrFail();
        $pending = PendingOrder::query()->firstOrFail();

        $this->assertSame($storeA->id, $cart->stock_location_id);
        $this->assertSame($storeA->id, $pending->stock_location_id);
        $this->assertSame(99.0, (float) $cart->items->first()->unit_price);
        $this->assertSame(2.0, (float) Inventory::query()->where('stock_location_id', $storeA->id)->where('product_variant_id', $variant->id)->value('reserved_quantity'));
        $this->assertSame(0.0, (float) Inventory::query()->where('stock_location_id', $storeB->id)->where('product_variant_id', $variant->id)->value('reserved_quantity'));
    }

    public function test_daily_offer_current_offers_are_filtered_by_store(): void
    {
        [$storeA, $storeB] = $this->stores();
        [, $variantA] = $this->variantWithInventory($storeA, $storeB);
        [, $variantB] = $this->variantWithInventory($storeB, $storeA);
        $offerA = DailyOffer::factory()->create(['product_variant_id' => $variantA->id, 'stock_location_id' => $storeA->id, 'allocated_quantity' => 5]);
        $offerB = DailyOffer::factory()->create(['product_variant_id' => $variantB->id, 'stock_location_id' => $storeB->id, 'allocated_quantity' => 5]);

        $ids = app(DailyOfferService::class)->currentOffers(8, $storeA->id)->pluck('id')->all();

        $this->assertContains($offerA->id, $ids);
        $this->assertNotContains($offerB->id, $ids);
    }

    public function test_store_manager_cannot_open_other_store_inventory(): void
    {
        [$storeA, $storeB] = $this->stores();
        [, $variant] = $this->variantWithInventory($storeA, $storeB);
        $otherInventory = Inventory::query()
            ->where('product_variant_id', $variant->id)
            ->where('stock_location_id', $storeB->id)
            ->firstOrFail();
        $manager = User::factory()->create([
            'role' => 'STORE_MANAGER',
            'assigned_store_id' => $storeA->id,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.inventories.show', $otherInventory))
            ->assertForbidden();
    }

    public function test_checkout_snapshots_store_and_brand_source(): void
    {
        [$storeA, $storeB, $customer] = $this->storesAndCustomer();
        [$product, $variant] = $this->variantWithInventory($storeA, $storeB);
        DeliverySlot::query()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9 AM - 11 AM',
            'status' => true,
            'display_order' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), [
                'product_variant_id' => $variant->id,
                'quantity' => 5,
            ])
            ->assertRedirect(route('cart.show'));

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $item = OrderItem::query()->firstOrFail();
        $order = $item->order;

        $this->assertSame($storeA->id, $order->stock_location_id);
        $this->assertSame($storeA->name, $order->store_name_snapshot);
        $this->assertSame($storeA->code, $order->store_code_snapshot);
        $this->assertSame($product->brand_id, $item->brand_id_snapshot);
        $this->assertSame($product->brand->name, $item->brand_name_snapshot);
    }

    public function test_announcements_use_compact_store_audience_and_customer_dismissal(): void
    {
        [$storeA, $storeB, $customer] = $this->storesAndCustomer();
        $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);

        $this->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => 'Store notice',
                'message' => 'Fresh stock arrives today',
                'audience_type' => 'stores',
                'store_ids' => [$storeA->id],
                'priority' => 10,
                'enabled' => 1,
                'dismissible' => 1,
            ])
            ->assertRedirect(route('admin.announcements.index'));

        $announcement = CustomerAnnouncement::query()->firstOrFail();

        $this->assertSame(1, $announcement->stores()->count());
        $this->assertSame(0, $announcement->customers()->count());
        $this->assertNotNull(app(CustomerAnnouncementService::class)->applicableFor($customer));

        $otherCustomer = Customer::factory()->create(['status' => true, 'assigned_store_id' => $storeB->id]);
        $this->assertNull(app(CustomerAnnouncementService::class)->applicableFor($otherCustomer));
    }

    public function test_marketing_banners_apply_store_targeting_and_max_five_rule(): void
    {
        [$storeA, $storeB, $customer] = $this->storesAndCustomer();

        foreach (range(1, 6) as $index) {
            $banner = CustomerMarketingBanner::query()->create([
                'title' => 'Banner '.$index,
                'image_path' => 'uploads/site/customer-banners/banner-'.$index.'.jpg',
                'enabled' => true,
                'priority' => $index,
            ]);
            $banner->stores()->sync([$storeA->id]);
        }

        $otherBanner = CustomerMarketingBanner::query()->create([
            'title' => 'Other store',
            'image_path' => 'uploads/site/customer-banners/other.jpg',
            'enabled' => true,
            'priority' => 99,
        ]);
        $otherBanner->stores()->sync([$storeB->id]);

        $banners = app(CustomerMarketingBannerService::class)->applicableFor($customer, 10);

        $this->assertCount(5, $banners);
        $this->assertFalse($banners->contains('id', $otherBanner->id));
    }

    public function test_homepage_store_section_override_falls_back_to_global_default(): void
    {
        [$storeA, $storeB] = $this->stores();
        HomepageSection::query()->create([
            'section_key' => 'daily_offers',
            'section_type' => 'daily_offers',
            'title' => 'Global Offers',
            'enabled' => true,
            'sort_order' => 50,
            'desktop_item_limit' => 8,
        ]);
        HomepageSection::query()->create([
            'stock_location_id' => $storeA->id,
            'section_key' => 'daily_offers',
            'section_type' => 'daily_offers',
            'title' => 'North Offers',
            'enabled' => true,
            'sort_order' => 50,
            'desktop_item_limit' => 8,
        ]);

        $customer = Customer::factory()->create(['status' => true, 'assigned_store_id' => $storeA->id]);
        $this->withSession(['customer_id' => $customer->id])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('North Offers');

        $customer->update(['assigned_store_id' => $storeB->id]);
        $this->withSession(['customer_id' => $customer->id])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Global Offers');
    }

    public function test_rapid_price_category_flag_and_price_history_cleanup(): void
    {
        $admin = User::factory()->create(['role' => 'SUPER_ADMIN']);

        $this->actingAs($admin)
            ->post(route('admin.categories.store'), [
                'name' => 'Vegetables',
                'slug' => 'vegetables',
                'status' => 1,
                'show_in_menu' => 1,
                'rapid_price_update_enabled' => 1,
            ])
            ->assertRedirect();

        $this->assertTrue((bool) Category::query()->where('slug', 'vegetables')->value('rapid_price_update_enabled'));

        [$storeA, $storeB] = $this->stores();
        [, $variant] = $this->variantWithInventory($storeA, $storeB);
        StoreVariantPriceHistory::query()->create([
            'stock_location_id' => $storeA->id,
            'product_variant_id' => $variant->id,
            'new_selling_price' => 10,
            'changed_at' => now('Asia/Kolkata')->subDays(91),
        ]);

        $this->artisan('prices:cleanup-history')->assertExitCode(0);
        $this->assertDatabaseCount('store_variant_price_histories', 0);
    }

    private function stores(): array
    {
        return [
            StockLocation::factory()->default()->create(['name' => 'Main Store', 'code' => 'MAIN']),
            StockLocation::factory()->create(['name' => 'North Store', 'code' => 'NORTH']),
        ];
    }

    private function storesAndCustomer(): array
    {
        [$storeA, $storeB] = $this->stores();
        $customer = Customer::factory()->create([
            'status' => true,
            'assigned_store_id' => $storeA->id,
        ]);
        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_default' => true,
            'is_approved' => true,
            'status' => true,
        ]);

        return [$storeA, $storeB, $customer];
    }

    private function variantWithInventory(StockLocation $primary, StockLocation $secondary): array
    {
        $brand = Brand::factory()->create(['name' => 'Snapshot Brand']);
        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'status' => true,
            'maximum_order_quantity' => 10,
        ]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'status' => true,
            'selling_price' => 120,
            'mrp' => 150,
        ]);
        $product->update(['default_variant_id' => $variant->id]);

        foreach ([$primary, $secondary] as $store) {
            Inventory::factory()->create([
                'product_variant_id' => $variant->id,
                'stock_location_id' => $store->id,
                'quantity_on_hand' => 10,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'status' => true,
            ]);
        }

        return [$product->fresh('brand'), $variant];
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'customer_email' => 'rohit@example.com',
            'customer_address_id' => CustomerAddress::query()->first()?->id,
            'delivery_address_line1' => 'House 12, Main Road',
            'delivery_address_line2' => 'Near Market',
            'delivery_city' => 'Patna',
            'delivery_state' => 'Bihar',
            'delivery_pincode' => '800001',
            'delivery_landmark' => 'Clock Tower',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => '9 AM - 11 AM',
            'payment_method' => 'cod',
        ];
    }
}
