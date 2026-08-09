<?php

namespace Tests\Feature;

use App\Domains\Cart\Services\CartService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_page_loads(): void
    {
        $this->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Your cart is empty.');
    }

    public function test_active_product_variant_can_be_added_to_cart_with_snapshots(): void
    {
        [$product, $variant, $inventory] = $this->purchasableVariant();

        $response = $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('cart.show'));

        $item = CartItem::query()->firstOrFail();

        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame('2.000', $item->quantity);
        $this->assertSame($product->name, $item->product_name_snapshot);
        $this->assertSame($variant->variant_name, $item->variant_name_snapshot);
        $this->assertSame($variant->sku, $item->sku_snapshot);
        $this->assertSame($product->hsn_code, $item->hsn_code_snapshot);
        $this->assertSame((string) $product->gst_rate, (string) $item->gst_rate_snapshot);
        $this->assertSame($variant->selling_price, $item->unit_price);
        $this->assertSame($variant->mrp, $item->mrp);
        $this->assertSame('10.000', $inventory->fresh()->quantity_on_hand);
    }

    public function test_customer_cart_quantity_must_be_whole_number(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => '1.049',
        ])->assertSessionHasErrors('quantity');

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));

        $item = CartItem::query()->firstOrFail();

        $this->patch(route('cart.items.update', $item), [
            'quantity' => '2.5',
        ])->assertSessionHasErrors('quantity');

        $this->patch(route('cart.items.update', $item), [
            'quantity' => 2,
        ])->assertRedirect(route('cart.show'));

        $this->assertSame('2.000', $item->fresh()->quantity);
    }

    public function test_coupon_validation_error_is_rendered_once_near_coupon_input(): void
    {
        [, $variant] = $this->purchasableVariant();
        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->followingRedirects()
            ->from(route('cart.show'))
            ->post(route('cart.coupon.apply'), ['code' => ''])
            ->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'The code field is required.'));
    }

    public function test_inactive_variant_and_inactive_product_cannot_be_added(): void
    {
        [, $inactiveVariant] = $this->purchasableVariant(variantOverrides: ['status' => false]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $inactiveVariant->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('cart');

        [, $variantUnderInactiveProduct] = $this->purchasableVariant(productOverrides: ['status' => false]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variantUnderInactiveProduct->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('cart');
    }

    public function test_add_and_update_validate_available_inventory(): void
    {
        [, $variant] = $this->purchasableVariant(inventoryOverrides: [
            'quantity_on_hand' => 5,
            'reserved_quantity' => 1,
            'damaged_quantity' => 1,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 4,
        ])->assertSessionHasErrors('cart');

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertRedirect(route('cart.show'));

        $item = CartItem::query()->firstOrFail();

        $this->patch(route('cart.items.update', $item), [
            'quantity' => 4,
        ])->assertSessionHasErrors('cart');
    }

    public function test_adding_same_variant_increments_quantity_and_totals_use_snapshots(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: [
            'selling_price' => 80,
            'mrp' => 100,
        ]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2]);

        $item = CartItem::query()->firstOrFail();
        $summary = app(CartService::class)->getCartSummary($item->cart->session_id);

        $this->assertSame('3.000', $item->quantity);
        $this->assertSame(240.0, $summary['subtotal']);
        $this->assertSame(60.0, $summary['savings']);

        $variant->update(['selling_price' => 10]);

        $summary = app(CartService::class)->getCartSummary($item->cart->session_id);
        $this->assertSame(240.0, $summary['subtotal']);
    }

    public function test_normal_cart_item_is_not_repriced_after_variant_selling_price_changes(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: [
            'selling_price' => 120,
            'mrp' => 150,
        ]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $item = CartItem::query()->firstOrFail();
        $variant->update(['selling_price' => 30]);

        $summary = app(CartService::class)->getCartSummary($item->cart->session_id);

        $this->assertSame('120.00', (string) $item->fresh()->unit_price);
        $this->assertSame(120.0, $summary['subtotal']);
    }

    public function test_daily_offer_cart_item_can_refresh_to_current_offer_price(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: [
            'selling_price' => 120,
            'mrp' => 150,
        ]);
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'daily_offer_id' => $offer->id,
        ]);
        $offer->update(['offer_price' => 80]);
        $item = CartItem::query()->firstOrFail();

        $summary = app(CartService::class)->getCartSummary($item->cart->session_id);

        $this->assertSame('80.00', (string) $item->fresh()->unit_price);
        $this->assertSame(80.0, $summary['subtotal']);
    }

    public function test_daily_offer_effective_quantity_uses_product_max_before_offer_max(): void
    {
        [, $variant] = $this->purchasableVariant(
            productOverrides: ['maximum_order_quantity' => 2],
            variantOverrides: ['selling_price' => 120, 'mrp' => 150],
            inventoryOverrides: ['quantity_on_hand' => 20]
        );
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 10,
            'max_quantity_per_order' => 5,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'daily_offer_id' => $offer->id,
        ])->assertSessionHasErrors(['cart' => 'Quantity is limited to 2 per order for this product.']);
    }

    public function test_available_inventory_lower_than_configured_max_is_effective_limit(): void
    {
        [, $variant] = $this->purchasableVariant(
            productOverrides: ['maximum_order_quantity' => 5],
            inventoryOverrides: ['quantity_on_hand' => 2]
        );

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertSessionHasErrors(['cart' => 'Quantity is limited to 2 per order for this product.']);
    }

    public function test_zero_stock_variant_cannot_be_added_even_with_forged_request(): void
    {
        [, $variant] = $this->purchasableVariant(inventoryOverrides: ['quantity_on_hand' => 0]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertSessionHasErrors(['cart' => 'This variant is out of stock.']);
    }

    public function test_existing_normal_cart_item_is_not_converted_when_daily_offer_starts_later(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: [
            'selling_price' => 120,
            'mrp' => 150,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertRedirect(route('cart.show'));
        $item = CartItem::query()->firstOrFail();
        $this->assertSame('normal', $item->sale_type);
        $this->assertNull($item->daily_offer_id);
        $this->assertSame('120.00', (string) $item->unit_price);
        $this->assertNull($item->cart->expires_at);

        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 5,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $summary = app(CartService::class)->getCartSummary($item->cart->session_id);

        $item->refresh();
        $this->assertSame('normal', $item->sale_type);
        $this->assertNull($item->daily_offer_id);
        $this->assertNull($item->cart->fresh()->expires_at);
        $this->assertSame('120.00', (string) $item->unit_price);
        $this->assertSame(120.0, $summary['subtotal']);
    }

    public function test_eligible_daily_offer_add_creates_daily_offer_cart_item(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: [
            'selling_price' => 120,
            'mrp' => 150,
        ]);
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 5,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'daily_offer_id' => $offer->id,
        ])->assertRedirect(route('cart.show'));

        $item = CartItem::query()->firstOrFail();
        $summary = app(CartService::class)->getCartSummary($item->cart->session_id);

        $this->assertSame('daily_offer', $item->sale_type);
        $this->assertSame($offer->id, $item->daily_offer_id);
        $this->assertTrue($item->cart->expires_at->isFuture());
        $this->assertSame('90.00', (string) $item->fresh()->unit_price);
        $this->assertSame(90.0, $summary['subtotal']);
        $this->assertSame(1, CartItem::query()->count());
    }

    public function test_normal_and_daily_offer_rows_for_same_variant_do_not_merge(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: [
            'selling_price' => 120,
            'mrp' => 150,
        ]);
        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1])
            ->assertRedirect(route('cart.show'));

        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 5,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'daily_offer_id' => $offer->id,
        ]);

        $items = CartItem::query()->orderBy('sale_type')->get();

        $this->assertCount(2, $items);
        $this->assertTrue($items->contains(fn (CartItem $item) => $item->sale_type === 'normal' && $item->daily_offer_id === null && (string) $item->unit_price === '120.00'));
        $this->assertTrue($items->contains(fn (CartItem $item) => $item->sale_type === 'daily_offer' && $item->daily_offer_id === $offer->id && (string) $item->unit_price === '90.00'));
    }

    public function test_normal_cart_cannot_consume_stock_allocated_to_active_daily_offer(): void
    {
        [, $variant] = $this->purchasableVariant(
            variantOverrides: ['selling_price' => 120, 'mrp' => 150],
            inventoryOverrides: ['quantity_on_hand' => 5]
        );
        DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 4,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 2])
            ->assertSessionHasErrors('cart');
    }

    public function test_daily_offer_cart_cannot_exceed_allocated_offer_quantity(): void
    {
        [, $variant] = $this->purchasableVariant(variantOverrides: ['selling_price' => 120, 'mrp' => 150]);
        $offer = DailyOffer::factory()->create([
            'product_variant_id' => $variant->id,
            'offer_price' => 90,
            'allocated_quantity' => 1,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'is_active' => true,
        ]);

        $this->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'daily_offer_id' => $offer->id,
        ])->assertSessionHasErrors('cart');
    }

    public function test_update_remove_and_clear_cart(): void
    {
        [, $variant] = $this->purchasableVariant();

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $item = CartItem::query()->firstOrFail();

        $this->patch(route('cart.items.update', $item), ['quantity' => 2])
            ->assertRedirect(route('cart.show'));

        $this->assertSame('2.000', $item->fresh()->quantity);

        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.show'));

        $this->assertSoftDeleted('cart_items', ['id' => $item->id]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('cart.clear'))->assertRedirect(route('cart.show'));

        $this->assertSame(0, CartItem::query()->count());
    }

    public function test_cart_item_cannot_be_updated_or_deleted_from_another_session(): void
    {
        [, $variant] = $this->purchasableVariant();
        $otherCart = Cart::factory()->create(['session_id' => 'other-session']);
        $item = CartItem::factory()->create([
            'cart_id' => $otherCart->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->patch(route('cart.items.update', $item), ['quantity' => 2])
            ->assertSessionHasErrors('cart');

        $this->delete(route('cart.items.destroy', $item))
            ->assertSessionHasErrors('cart');

        $this->assertNotSoftDeleted('cart_items', ['id' => $item->id]);
    }

    public function test_product_card_and_detail_show_add_to_cart_controls(): void
    {
        [$product] = $this->purchasableVariant();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertSee('Add to Cart');

        $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Add to Cart')
            ->assertSee('cartVariantId');
    }

    public function test_no_disallowed_commerce_modules_or_catalog_stock_fields_are_created(): void
    {
        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertNotContains('cashback', $uris);
        $this->assertNotContains('coupons', $uris);

        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }

    private function purchasableVariant(array $productOverrides = [], array $variantOverrides = [], array $inventoryOverrides = []): array
    {
        $product = Product::factory()->create(array_merge([
            'name' => 'Wheat Atta',
            'slug' => fake()->unique()->slug(),
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
}
