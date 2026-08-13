<?php

namespace App\Domains\Cart\Services;

use App\Domains\Cart\Contracts\CartRepositoryInterface;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\DailyOffer;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Session\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CartService
{
    public const SALE_TYPE_NORMAL = 'normal';

    public const SALE_TYPE_DAILY_OFFER = 'daily_offer';

    public function __construct(
        private readonly CartRepositoryInterface $repository,
        private readonly InventoryService $inventoryService,
        private readonly PendingOrderService $pendingOrderService,
        private readonly BusinessSettingService $settings
    ) {}

    public function getOrCreateCartForSession(string $sessionId): Cart
    {
        return DB::transaction(function () use ($sessionId) {
            if ($customerId = $this->customerIdFromSessionIdentifier($sessionId)) {
                $cart = $this->repository->activeCartForCustomer($customerId);

                if ($cart) {
                    return $cart;
                }

                try {
                    return $this->repository->createCartForCustomer($customerId, $sessionId);
                } catch (QueryException $exception) {
                    if (! $this->isUniqueViolation($exception, 'carts_one_active_per_customer_unique')) {
                        throw $exception;
                    }

                    return $this->repository->activeCartForCustomer($customerId) ?? throw $exception;
                }
            }

            return $this->repository->activeCartForSession($sessionId)
                ?? $this->repository->createCartForSession($sessionId);
        });
    }

    public function sessionIdentifier(Store $session): string
    {
        if ($session->has('customer_id')) {
            $sessionId = 'customer:'.$session->get('customer_id');
            $session->put('cart_session_id', $sessionId);

            return $sessionId;
        }

        if (! $session->has('cart_session_id')) {
            $session->put('cart_session_id', $session->getId() ?: (string) Str::uuid());
        }

        return (string) $session->get('cart_session_id');
    }

    public function cartForSession(string $sessionId): Cart
    {
        $cart = $this->getOrCreateCartForSession($sessionId);
        $this->pendingOrderService->expireIfNeeded($cart);
        $this->pendingOrderService->triggerReminderIfDue($cart);

        return $this->repository->cartWithItems($cart->fresh());
    }

    public function addItem(string $sessionId, int $productVariantId, float $quantity, ?int $dailyOfferId = null): CartItem
    {
        $quantity = $this->normalizeCustomerQuantity($quantity);

        return DB::transaction(function () use ($sessionId, $productVariantId, $quantity, $dailyOfferId) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $variant = ProductVariant::query()
                ->with(['product', 'attributeValues.attribute'])
                ->findOrFail($productVariantId);
            $dailyOffer = $dailyOfferId ? $this->currentDailyOfferForVariant($variant->id, $dailyOfferId) : null;

            if ($dailyOfferId !== null && $dailyOffer === null) {
                throw new InvalidArgumentException('Daily offer is no longer available.');
            }

            $saleType = $dailyOffer ? self::SALE_TYPE_DAILY_OFFER : self::SALE_TYPE_NORMAL;

            $this->validateVariantIsPurchasable($variant);
            $existingItem = $this->repository->findItemInCart($cart, $variant->id, $saleType, $dailyOffer?->id);
            $existingQuantity = $existingItem && ! $existingItem->trashed()
                ? (float) $existingItem->quantity
                : 0;
            $targetQuantity = $quantity + $existingQuantity;

            $this->validateEffectiveQuantityLimit($variant, $dailyOffer, $targetQuantity, $existingQuantity);

            if ($existingItem) {
                $wasTrashed = $existingItem->trashed();

                if ($existingItem->trashed()) {
                    $existingItem->restore();
                }

                $item = $this->repository->updateItem($existingItem, array_merge([
                    'quantity' => $targetQuantity,
                ], $this->prepareCartItemSnapshot($variant, $dailyOffer), [
                    'daily_offer_reserved_until' => $dailyOffer
                        ? ($wasTrashed ? $this->dailyOfferHoldExpiresAt() : ($existingItem->daily_offer_reserved_until ?? $this->dailyOfferHoldExpiresAt()))
                        : null,
                ]));
                $this->recordCartMutation($cart);
                $this->pendingOrderService->afterItemAddedOrUpdated($cart);

                return $item;
            }

            $item = CartItem::query()->create(array_merge([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'daily_offer_reserved_until' => $dailyOffer ? $this->dailyOfferHoldExpiresAt() : null,
            ], $this->prepareCartItemSnapshot($variant, $dailyOffer)));

            $this->recordCartMutation($cart);
            $this->pendingOrderService->afterItemAddedOrUpdated($cart);

            return $item;
        });
    }

    public function updateItemQuantity(string $sessionId, CartItem $cartItem, float $quantity): CartItem
    {
        $quantity = $this->normalizeCustomerQuantity($quantity);

        return DB::transaction(function () use ($sessionId, $cartItem, $quantity) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $cartItem = $this->repository->findItem($cartItem->id);
            $this->ensureCartItemBelongsToCurrentCart($cart, $cartItem);
            $variant = $cartItem->productVariant?->load('product');
            $dailyOffer = $variant && $this->isDailyOfferSnapshot($cart, $cartItem, $variant)
                ? $this->currentDailyOfferForVariant($cartItem->product_variant_id, $cartItem->daily_offer_id)
                : null;

            $this->validateEffectiveQuantityLimit($variant, $dailyOffer, $quantity, (float) $cartItem->quantity);

            $updated = $this->repository->updateItem($cartItem, ['quantity' => $quantity]);
            $this->recordCartMutation($cart);
            $this->pendingOrderService->afterItemAddedOrUpdated($cart);

            return $updated;
        });
    }

    public function removeItem(string $sessionId, CartItem $cartItem): bool
    {
        return DB::transaction(function () use ($sessionId, $cartItem) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $cartItem = $this->repository->findItem($cartItem->id);
            $this->ensureCartItemBelongsToCurrentCart($cart, $cartItem);

            $deleted = $this->repository->deleteItem($cartItem);
            $this->recordCartMutation($cart);
            $this->pendingOrderService->afterItemRemoved($cart, $cartItem);

            return $deleted;
        });
    }

    public function clearCart(string $sessionId): int
    {
        return DB::transaction(function () use ($sessionId) {
            $cart = $this->getOrCreateCartForSession($sessionId);
            $count = $this->repository->clearCart($cart);
            $this->recordCartMutation($cart);
            $this->pendingOrderService->afterCartCleared($cart);

            return $count;
        });
    }

    public function getCartSummary(string $sessionId): array
    {
        $baseCart = $this->getOrCreateCartForSession($sessionId);
        $cartExpired = $this->pendingOrderService->expireIfNeeded($baseCart);
        $this->pendingOrderService->triggerReminderIfDue($baseCart);
        $cart = $this->refreshCartPrices($this->repository->cartWithItems($baseCart->fresh()));

        return [
            'cart' => $cart,
            'cart_expired' => $cartExpired,
            'item_count' => (float) $cart->items->sum('quantity'),
            'line_count' => $cart->items->count(),
            'subtotal' => $this->calculateSubtotal($cart),
            'savings' => $this->calculateSavings($cart),
            'coupon_discount' => (float) $cart->coupon_discount_amount,
            'applied_coupon' => $cart->coupon,
            'pending_order' => $this->pendingOrderService->activeForCart($cart),
        ];
    }

    public function status(string $sessionId): array
    {
        $summary = $this->getCartSummary($sessionId);
        $pending = $summary['pending_order'];

        return [
            'cart_id' => $summary['cart']->id,
            'revision' => $summary['cart']->revision,
            'item_count' => $summary['item_count'],
            'line_count' => $summary['line_count'],
            'subtotal' => $summary['subtotal'],
            'pending_reference' => $pending?->reference,
            'expires_at' => $pending?->expires_at?->toIso8601String(),
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
        $cart->loadMissing(['items.productVariant.product', 'items.productVariant.attributeValues.attribute']);

        foreach ($cart->items as $item) {
            $variant = $item->productVariant;

            if (! $variant || ! $variant->product) {
                continue;
            }

            if (! $this->isDailyOfferSnapshot($cart, $item, $variant)) {
                continue;
            }

            $dailyOffer = $this->currentDailyOfferForVariant($variant->id, $item->daily_offer_id);

            if (! $dailyOffer) {
                continue;
            }

            $expectedPrice = $dailyOffer->offer_price;

            if ((float) $item->unit_price !== (float) $expectedPrice) {
                $this->repository->updateItem($item, $this->prepareCartItemSnapshot($variant, $dailyOffer));
            }
        }

        return $this->repository->cartWithItems($cart->fresh());
    }

    public function validateDailyOfferHold(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            if ($item->sale_type === self::SALE_TYPE_DAILY_OFFER
                && $item->daily_offer_id !== null
                && (! $item->daily_offer_reserved_until || $item->daily_offer_reserved_until->isPast())) {
                throw new InvalidArgumentException('Daily offer reservation expired. Please review your cart before checkout.');
            }
        }
    }

    public function prepareCartItemSnapshot(ProductVariant $variant, ?DailyOffer $dailyOffer = null): array
    {
        $product = $variant->product;

        return [
            'unit_price' => $dailyOffer?->offer_price ?? $variant->selling_price,
            'sale_type' => $dailyOffer ? self::SALE_TYPE_DAILY_OFFER : self::SALE_TYPE_NORMAL,
            'daily_offer_id' => $dailyOffer?->id,
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

    public function recordCartMutation(Cart $cart): void
    {
        $cart->increment('revision');
    }

    public function syncPendingLifecycle(Cart $cart): void
    {
        $this->pendingOrderService->afterItemAddedOrUpdated($cart);
    }

    private function customerIdFromSessionIdentifier(string $sessionId): ?int
    {
        if (! str_starts_with($sessionId, 'customer:')) {
            return null;
        }

        $customerId = (int) Str::after($sessionId, 'customer:');

        return $customerId > 0 ? $customerId : null;
    }

    private function isUniqueViolation(QueryException $exception, string $indexName): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23000'
            && str_contains($exception->getMessage(), $indexName);
    }

    private function currentDailyOfferForVariant(int $productVariantId, ?int $dailyOfferId = null): ?DailyOffer
    {
        return DailyOffer::query()
            ->current()
            ->where('product_variant_id', $productVariantId)
            ->when($dailyOfferId !== null, fn ($query) => $query->whereKey($dailyOfferId))
            ->with(['cartItems.cart', 'orderItems'])
            ->orderBy('display_order')
            ->get()
            ->first(fn (DailyOffer $offer) => $offer->availableOfferQuantity() > 0 || $dailyOfferId !== null);
    }

    private function isDailyOfferSnapshot(Cart $cart, CartItem $item, ProductVariant $variant): bool
    {
        return $item->sale_type === self::SALE_TYPE_DAILY_OFFER && $item->daily_offer_id !== null;
    }

    private function dailyOfferHoldExpiresAt(): Carbon
    {
        $holdMinutes = max(1, (int) $this->settings->get('checkout.daily_offer_hold_minutes', 15));

        return now()->addMinutes($holdMinutes);
    }

    private function validateEffectiveQuantityLimit(?ProductVariant $variant, ?DailyOffer $dailyOffer, float $targetQuantity, float $existingQuantity = 0): void
    {
        if (! $variant) {
            throw new InvalidArgumentException('This product variant is not available.');
        }

        $effectiveMax = $this->effectiveMaximumQuantity($variant, $dailyOffer, $existingQuantity);

        if ($effectiveMax <= 0) {
            throw new InvalidArgumentException('This variant is out of stock.');
        }

        if ($targetQuantity > $effectiveMax) {
            throw new InvalidArgumentException('Quantity is limited to '.(int) floor($effectiveMax).' per order for this product.');
        }
    }

    private function effectiveMaximumQuantity(ProductVariant $variant, ?DailyOffer $dailyOffer, float $existingQuantity = 0): float
    {
        $limits = [];
        $maximumOrderQuantity = $variant->product?->maximum_order_quantity;

        if ($maximumOrderQuantity !== null) {
            $limits[] = (float) $maximumOrderQuantity;
        }

        if ($dailyOffer) {
            if ($dailyOffer->max_quantity_per_order) {
                $limits[] = (float) $dailyOffer->max_quantity_per_order;
            }

            $limits[] = $existingQuantity + $dailyOffer->availableOfferQuantity();
            $limits[] = $this->inventoryService->getAvailableQuantity($variant->id);

            return max(0, min($limits));
        }

        $limits[] = $this->normalSellableQuantity($variant->id);

        return max(0, min($limits));
    }

    private function normalSellableQuantity(int $productVariantId): float
    {
        $allocatedRemaining = DailyOffer::query()
            ->active()
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now(config('app.timezone'))))
            ->where('product_variant_id', $productVariantId)
            ->with(['cartItems.cart', 'orderItems'])
            ->get()
            ->sum(fn (DailyOffer $offer) => max(0, (float) $offer->allocated_quantity - $offer->soldQuantity()));

        return max(0, $this->inventoryService->getAvailableQuantity($productVariantId) - $allocatedRemaining);
    }
}
