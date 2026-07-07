<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WishlistManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_wishlist_redirects_to_customer_login(): void
    {
        $this->get(route('wishlist.index'))
            ->assertRedirect(route('customer.login'));
    }

    public function test_logged_in_customer_can_add_item_to_wishlist(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.store'), [
                'product_variant_id' => $variant->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('wishlist_items', [
            'customer_id' => $customer->id,
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
        ]);
    }

    public function test_duplicate_wishlist_add_does_not_create_duplicate_item(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.store'), ['product_variant_id' => $variant->id]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.store'), ['product_variant_id' => $variant->id]);

        $this->assertSame(1, WishlistItem::query()
            ->where('customer_id', $customer->id)
            ->where('product_variant_id', $variant->id)
            ->count());
    }

    public function test_deleted_wishlist_item_is_restored_on_duplicate_add(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        $item = WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
        $item->delete();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.store'), ['product_variant_id' => $variant->id])
            ->assertRedirect();

        $this->assertSame(1, WishlistItem::withTrashed()
            ->where('customer_id', $customer->id)
            ->where('product_variant_id', $variant->id)
            ->count());
        $this->assertNotSoftDeleted('wishlist_items', ['id' => $item->id]);
    }

    public function test_logged_in_customer_can_remove_wishlist_item(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        $item = WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->delete(route('wishlist.items.destroy', $item))
            ->assertRedirect();

        $this->assertSoftDeleted('wishlist_items', ['id' => $item->id]);
    }

    public function test_customer_cannot_remove_another_customers_wishlist_item(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        $item = WishlistItem::factory()->create([
            'customer_id' => $otherCustomer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->delete(route('wishlist.items.destroy', $item))
            ->assertNotFound();

        $this->assertNotSoftDeleted('wishlist_items', ['id' => $item->id]);
    }

    public function test_wishlist_page_shows_customer_items_only(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        [$product, $variant] = $this->activeVariant(['name' => 'Basmati Rice']);
        [$otherProduct, $otherVariant] = $this->activeVariant(['name' => 'Sunflower Oil']);

        WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);
        WishlistItem::factory()->create([
            'customer_id' => $otherCustomer->id,
            'product_id' => $otherProduct->id,
            'product_variant_id' => $otherVariant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee('Basmati Rice')
            ->assertDontSee('Sunflower Oil');
    }

    public function test_wishlist_item_references_product_variant_identity(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();

        WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);

        $this->assertTrue(Schema::hasColumn('wishlist_items', 'product_variant_id'));
        $this->assertDatabaseHas('wishlist_items', [
            'customer_id' => $customer->id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function test_add_to_cart_from_wishlist_still_works(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);
        WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('wishlist.index'))
            ->assertOk()
            ->assertSee('Move to Cart');

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.move-to-cart', WishlistItem::query()->firstOrFail()))
            ->assertRedirect();

        $this->assertDatabaseHas('cart_items', [
            'product_variant_id' => $variant->id,
        ]);
        $this->assertSame(1, CartItem::query()->count());
        $this->assertSoftDeleted('wishlist_items', [
            'customer_id' => $customer->id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function test_wishlist_active_state_and_count_render_for_saved_variants(): void
    {
        $customer = Customer::factory()->create();
        [$product, $variant] = $this->activeVariant(['name' => 'Toned Milk']);

        WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('gk-wishlist-button is-active', false)
            ->assertSee('gk-wishlist-link is-active', false)
            ->assertSee('gk-mobile-badge', false);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertSee('Saved in Wishlist');
    }

    public function test_deleting_cart_item_after_move_does_not_break_future_wishlist_and_cart_usage(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);
        $item = WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.move-to-cart', $item))
            ->assertRedirect();

        $cartItem = CartItem::query()->firstOrFail();
        $session = [
            'customer_id' => $customer->id,
            'cart_session_id' => $cartItem->cart->session_id,
        ];

        $this->withSession($session)
            ->delete(route('cart.items.destroy', $cartItem))
            ->assertRedirect(route('cart.show'));

        $this->withSession($session)
            ->post(route('wishlist.items.store'), ['product_variant_id' => $variant->id])
            ->assertRedirect();

        $this->assertNotSoftDeleted('wishlist_items', [
            'customer_id' => $customer->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->withSession($session)
            ->post(route('wishlist.items.move-to-cart', WishlistItem::query()->firstOrFail()))
            ->assertRedirect();

        $this->assertSame(1, CartItem::query()->where('product_variant_id', $variant->id)->count());
    }

    public function test_trying_to_move_removed_wishlist_item_does_not_500(): void
    {
        $customer = Customer::factory()->create();
        [, $variant] = $this->activeVariant();
        $item = WishlistItem::factory()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
        $item->delete();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('wishlist.items.move-to-cart', $item->id))
            ->assertRedirect()
            ->assertSessionHasErrors('wishlist');
    }

    private function activeVariant(array $productOverrides = [], array $variantOverrides = []): array
    {
        $product = Product::factory()->create(array_merge([
            'status' => true,
        ], $productOverrides));

        $variant = ProductVariant::factory()->default()->create(array_merge([
            'product_id' => $product->id,
            'status' => true,
            'mrp' => 100,
            'selling_price' => 90,
        ], $variantOverrides));

        $product->update(['default_variant_id' => $variant->id]);

        return [$product, $variant];
    }
}
