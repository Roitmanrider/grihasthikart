<?php

namespace Tests\Feature;

use App\Domains\Cart\Services\CartService;
use App\Models\Cart;
use App\Models\CartItem;
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
