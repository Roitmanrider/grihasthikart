<?php

namespace App\Domains\Cart\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Cart;
use App\Models\CartItem;

interface CartRepositoryInterface extends RepositoryInterface
{
    public function activeCartForSession(string $sessionId): ?Cart;

    public function activeCartForCustomer(int $customerId): ?Cart;

    public function createCartForSession(string $sessionId, ?int $stockLocationId = null): Cart;

    public function createCartForCustomer(int $customerId, string $sessionId, ?int $stockLocationId = null): Cart;

    public function cartWithItems(Cart $cart): Cart;

    public function findItem(int $id): CartItem;

    public function findItemInCart(Cart $cart, int $productVariantId, string $saleType = 'normal', ?int $dailyOfferId = null): ?CartItem;

    public function updateItem(CartItem $item, array $data): CartItem;

    public function deleteItem(CartItem $item): bool;

    public function clearCart(Cart $cart): int;
}
