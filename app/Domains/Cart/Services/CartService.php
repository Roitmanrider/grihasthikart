<?php

namespace App\Domains\Cart\Services;

use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Domains\Inventory\Services\InventoryService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DailyOffer;
use App\Models\ProductVariant;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    public function __construct(
        private readonly CartRepositoryInterface $repository,
        private readonly InventoryService $inventoryService
    ) {}

    public function getOrCreateCartForSession(string $sessionId): Cart
    {
        return DB::transaction(function () use ($sessionId) {
            return $this->repository->activeCartForSession($sessionId)
                ?? $this->repository->createCartForSession($sessionId);
        });
    }

    public function sessionIdentifier(Store $session): string
    {
        if (! $session->has('cart_session_id')) {
            $session->put('cart_session_id', $session->getId() ?: (string) Str::uuid());
        }

        return (string) $session->get('cart_session_id');
    }

    public function cartForSession(string $sessionId): Cart
    {
        return $this->repository->cartWithItems($this->getOrCreateCartForSession($sessionId));
    }

    public function addItem(string $sessionId, int $productVariantId, float $quantity): CartItem
    {
        $quantity = $this->normalizeCustomerQuantity($quantity);

        return DB::transaction(function () use ($sessionId, $productVariantId, $quantity) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $variant = ProductVariant::query()
                ->with(['product', 'attributeValues.attribute'])
                ->findOrFail($productVariantId);
            $dailyOffer = $this->currentDailyOfferForVariant($variant->id);

            $this->validateVariantIsPurchasable($variant);
            $this->applyDailyOfferHoldIfNeeded($cart, $dailyOffer);

            $existingItem = $this->repository->findItemInCart($cart, $variant->id);
            $existingQuantity = $existingItem && ! $existingItem->trashed()
                ? (float) $existingItem->quantity
                : 0;
            $targetQuantity = $quantity + $existingQuantity;

            $this->validateDailyOfferQuantity($dailyOffer, $targetQuantity);
            $this->validateSufficientStock($variant->id, $targetQuantity);

            if ($existingItem) {
                if ($existingItem->trashed()) {
                    $existingItem->restore();
                }

                return $this->repository->updateItem($existingItem, array_merge([
                    'quantity' => $targetQuantity,
                ], $this->prepareCartItemSnapshot($variant, $dailyOffer)));
            }

            return CartItem::query()->create(array_merge([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
            ], $this->prepareCartItemSnapshot($variant, $dailyOffer)));
        });
    }

    public function updateItemQuantity(string $sessionId, CartItem $cartItem, float $quantity): CartItem
    {
        $quantity = $this->normalizeCustomerQuantity($quantity);

        return DB::transaction(function () use ($sessionId, $cartItem, $quantity) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $cartItem = $this->repository->findItem($cartItem->id);
            $this->ensureCartItemBelongsToCurrentCart($cart, $cartItem);
            $this->validateDailyOfferQuantity($this->currentDailyOfferForVariant($cartItem->product_variant_id), $quantity);
            $this->validateSufficientStock($cartItem->product_variant_id, $quantity);

            return $this->repository->updateItem($cartItem, ['quantity' => $quantity]);
        });
    }

    public function removeItem(string $sessionId, CartItem $cartItem): bool
    {
        return DB::transaction(function () use ($sessionId, $cartItem) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $cartItem = $this->repository->findItem($cartItem->id);
            $this->ensureCartItemBelongsToCurrentCart($cart, $cartItem);

            return $this->repository->deleteItem($cartItem);
        });
    }

    public function clearCart(string $sessionId): int
    {
        return DB::transaction(function () use ($sessionId) {
            return $this->repository->clearCart($this->getOrCreateCartForSession($sessionId));
        });
    }

    public function getCartSummary(string $sessionId): array
    {
        $cart = $this->cartForSession($sessionId);

        return [
            'cart' => $cart,
            'item_count' => (float) $cart->items->sum('quantity'),
            'line_count' => $cart->items->count(),
            'subtotal' => $this->calculateSubtotal($cart),
            'savings' => $this->calculateSavings($cart),
            'coupon_discount' => (float) $cart->coupon_discount_amount,
            'applied_coupon' => $cart->coupon,
        ];
    }

    public function calculateSubtotal(Cart $cart): float
    {
        return (float) $cart->items->sum(fn (CartItem $item) => $item->line_total);
    }

    public function calculateSavings(Cart $cart): float
    {
        return (float) $cart->items->sum(fn (CartItem $item) => $item->line_savings);
    }

    public function validateVariantIsPurchasable(ProductVariant $variant): void
    {
        if (! $variant->status || $variant->trashed()) {
            throw new InvalidArgumentException('This product variant is not available.');
        }

        if (! $variant->product || ! $variant->product->status || $variant->product->trashed()) {
            throw new InvalidArgumentException('This product is not available.');
        }
    }

    public function validateSufficientStock(int $productVariantId, float $quantity): void
    {
        if ($this->inventoryService->getAvailableQuantity($productVariantId) < $quantity) {
            throw new InvalidArgumentException('Requested quantity exceeds available stock.');
        }
    }

    public function refreshCartPrices(Cart $cart): Cart
    {
        return $cart;
    }

    public function prepareCartItemSnapshot(ProductVariant $variant, ?DailyOffer $dailyOffer = null): array
    {
        $product = $variant->product;

        return [
            'unit_price' => $dailyOffer?->offer_price ?? $variant->selling_price,
            'mrp' => $variant->mrp,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->variant_name,
            'sku_snapshot' => $variant->sku,
            'hsn_code_snapshot' => $product->hsn_code,
            'gst_rate_snapshot' => $product->gst_rate,
            'attributes_snapshot' => $variant->attributeValues
                ->map(fn ($value) => [
                    'attribute' => $value->attribute?->name,
                    'value' => $value->value,
                ])
                ->values()
                ->all(),
        ];
    }

    public function ensureCartItemBelongsToCurrentCart(Cart $cart, CartItem $cartItem): void
    {
        if ($cartItem->cart_id !== $cart->id) {
            throw new InvalidArgumentException('This cart item does not belong to the current session.');
        }
    }

    private function normalizeCustomerQuantity(float $quantity): int
    {
        if ($quantity < 1 || floor($quantity) !== $quantity) {
            throw new InvalidArgumentException('Cart quantity must be a whole number of at least one.');
        }

        return (int) $quantity;
    }

    private function currentDailyOfferForVariant(int $productVariantId): ?DailyOffer
    {
        return DailyOffer::query()
            ->current()
            ->where('product_variant_id', $productVariantId)
            ->orderBy('display_order')
            ->first();
    }

    private function applyDailyOfferHoldIfNeeded(Cart $cart, ?DailyOffer $dailyOffer): void
    {
        if (! $dailyOffer) {
            return;
        }

        $holdExpiresAt = now()->addMinutes(30);

        if ($cart->expires_at === null || $cart->expires_at->greaterThan($holdExpiresAt)) {
            $cart->update(['expires_at' => $holdExpiresAt]);
        }
    }

    private function validateDailyOfferQuantity(?DailyOffer $dailyOffer, float $quantity): void
    {
        if ($dailyOffer?->max_quantity_per_order && $quantity > $dailyOffer->max_quantity_per_order) {
            throw new InvalidArgumentException('Daily offer quantity is limited to '.$dailyOffer->max_quantity_per_order.' per order.');
        }
    }
}
