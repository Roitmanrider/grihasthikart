<?php

namespace Tests\Unit;

use App\Domains\Cart\Repositories\CartRepository;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_or_creates_active_cart_for_session(): void
    {
        $repository = new CartRepository(new Cart);

        $this->assertNull($repository->activeCartForSession('session-a'));

        $cart = $repository->createCartForSession('session-a');

        $this->assertSame($cart->id, $repository->activeCartForSession('session-a')?->id);
    }

    public function test_it_finds_updates_deletes_and_clears_items(): void
    {
        $cart = Cart::factory()->create();
        $variant = ProductVariant::factory()->create();
        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $repository = new CartRepository(new Cart);

        $this->assertSame($item->id, $repository->findItemInCart($cart, $variant->id)?->id);
        $this->assertSame('3.000', $repository->updateItem($item, ['quantity' => 3])->quantity);
        $this->assertSame(1, $repository->clearCart($cart));
        $this->assertSame(1, CartItem::onlyTrashed()->count());
    }
}
