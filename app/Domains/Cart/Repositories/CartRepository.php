<?php

namespace App\Domains\Cart\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;

class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }

    public function activeCartForSession(string $sessionId): ?Cart
    {
        return $this->model
            ->newQuery()
            ->active()
            ->where('session_id', $sessionId)
            ->first();
    }

    public function createCartForSession(string $sessionId): Cart
    {
        return $this->model->newQuery()->create([
            'session_id' => $sessionId,
            'status' => 'active',
        ]);
    }

    public function cartWithItems(Cart $cart): Cart
    {
        return $cart->load([
            'coupon',
            'items.productVariant.product.primaryImage',
            'items.productVariant.primaryImage',
        ]);
    }

    public function findItem(int $id): CartItem
    {
        return CartItem::query()->findOrFail($id);
    }

    public function findItemInCart(Cart $cart, int $productVariantId): ?CartItem
    {
        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->where('product_variant_id', $productVariantId)
            ->first();
    }

    public function updateItem(CartItem $item, array $data): CartItem
    {
        $item->update($data);

        return $item;
    }

    public function deleteItem(CartItem $item): bool
    {
        return (bool) $item->delete();
    }

    public function clearCart(Cart $cart): int
    {
        return CartItem::query()
            ->where('cart_id', $cart->id)
            ->delete();
    }
}
