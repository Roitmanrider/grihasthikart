<?php

namespace App\Domains\Cart\Services;

use App\Domains\Notification\Services\NotificationService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\PendingOrder;
use App\Models\PendingOrderItem;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class PendingOrderService
{
    public function __construct(
        private readonly BusinessSettingService $settings,
        private readonly NotificationService $notifications
    ) {}

    public function holdMinutes(): int
    {
        return max(1, (int) $this->settings->get('checkout.cart_hold_minutes', 120));
    }

    public function reminderMinutes(): int
    {
        return max(1, (int) $this->settings->get('checkout.cart_reminder_minutes', 30));
    }

    public function activeForCart(Cart $cart): ?PendingOrder
    {
        if (! $cart->customer_id) {
            return null;
        }

        return PendingOrder::query()
            ->active()
            ->where('cart_id', $cart->id)
            ->with(['activeItems', 'customer'])
            ->first();
    }

    public function ensureActiveForCart(Cart $cart): ?PendingOrder
    {
        $cart->loadMissing('items.productVariant');

        if (! $cart->customer_id || $cart->items->isEmpty()) {
            return null;
        }

        $pending = PendingOrder::query()
            ->active()
            ->where('cart_id', $cart->id)
            ->lockForUpdate()
            ->first();

        if (! $pending) {
            $pending = $this->createActivePendingWithRetry($cart);

            if ($pending->wasRecentlyCreated) {
                $this->notifications->notifyAdminPendingCartStarted($pending);
            }
        }

        $this->syncSnapshots($pending, $cart);

        return $pending->fresh(['activeItems', 'customer']);
    }

    public function afterItemAddedOrUpdated(Cart $cart): void
    {
        $this->ensureActiveForCart($cart->fresh(['items.productVariant']));
        $this->triggerReminderIfDue($cart);
    }

    public function afterItemRemoved(Cart $cart, CartItem $removedItem): void
    {
        $cart = $cart->fresh(['items.productVariant']);
        $pending = $this->activeForCart($cart);

        if (! $pending) {
            return;
        }

        $pendingItem = PendingOrderItem::query()
            ->where('pending_order_id', $pending->id)
            ->where('cart_item_id', $removedItem->id)
            ->whereNull('removed_at')
            ->first();

        if ($pendingItem) {
            $pendingItem->update(['removed_at' => now()]);
        }

        $anchor = PendingOrderItem::query()
            ->where('pending_order_id', $pending->id)
            ->orderBy('added_at')
            ->orderBy('id')
            ->first();

        if ($anchor && $anchor->cart_item_id === $removedItem->id) {
            $this->close($pending, PendingOrder::CLOSE_ANCHOR_REMOVED);

            if ($cart->items->isNotEmpty()) {
                $this->ensureActiveForCart($cart);
            }

            return;
        }

        $this->syncSnapshots($pending, $cart);
    }

    public function afterCartCleared(Cart $cart): void
    {
        $pending = $this->activeForCart($cart);

        if (! $pending) {
            return;
        }

        PendingOrderItem::query()
            ->where('pending_order_id', $pending->id)
            ->whereNull('removed_at')
            ->update(['removed_at' => now()]);

        $this->close($pending, PendingOrder::CLOSE_CART_CLEARED);
    }

    public function expireIfNeeded(Cart $cart): bool
    {
        $pending = $this->activeForCart($cart);

        if (! $pending || $pending->expires_at->isFuture()) {
            return false;
        }

        DB::transaction(function () use ($cart, $pending) {
            $lockedPending = PendingOrder::query()
                ->whereKey($pending->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedPending || $lockedPending->status !== PendingOrder::STATUS_ACTIVE || $lockedPending->expires_at->isFuture()) {
                return;
            }

            $this->expireLocked($lockedPending, $cart);
        });

        return true;
    }

    public function triggerReminderIfDue(Cart $cart): void
    {
        $pending = $this->activeForCart($cart);

        if (! $pending || $pending->reminder_sent_at || $pending->expires_at->isPast()) {
            return;
        }

        if ($pending->started_at->copy()->addMinutes($this->reminderMinutes())->isFuture()) {
            return;
        }

        if ($cart->items()->count() === 0) {
            return;
        }

        $remainingMinutes = max(1, now()->diffInMinutes($pending->expires_at, false));
        $pending->update(['reminder_sent_at' => now()]);

        $this->notifications->notifyCustomerPendingCartReminder($pending, $remainingMinutes);
        $this->notifications->notifyAdminPendingCartReminder($pending);
    }

    public function processDue(int $chunkSize = 100): array
    {
        $summary = ['reminded' => 0, 'expired' => 0];

        PendingOrder::query()
            ->active()
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhere(function ($query) {
                        $query->whereNull('reminder_sent_at')
                            ->where('started_at', '<=', now()->subMinutes($this->reminderMinutes()));
                    });
            })
            ->orderBy('id')
            ->chunkById($chunkSize, function ($pendingOrders) use (&$summary) {
                foreach ($pendingOrders as $pendingOrder) {
                    DB::transaction(function () use ($pendingOrder, &$summary) {
                        $lockedPending = PendingOrder::query()
                            ->whereKey($pendingOrder->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $lockedPending || $lockedPending->status !== PendingOrder::STATUS_ACTIVE) {
                            return;
                        }

                        $cart = Cart::query()
                            ->whereKey($lockedPending->cart_id)
                            ->lockForUpdate()
                            ->first();

                        if (! $cart) {
                            $this->close($lockedPending, PendingOrder::CLOSE_EXPIRED);
                            $summary['expired']++;

                            return;
                        }

                        if ($lockedPending->expires_at->isPast() || $lockedPending->expires_at->equalTo(now())) {
                            $this->expireLocked($lockedPending, $cart);
                            $summary['expired']++;

                            return;
                        }

                        if (! $lockedPending->reminder_sent_at
                            && $lockedPending->started_at->copy()->addMinutes($this->reminderMinutes())->isPast()
                            && $cart->items()->count() > 0) {
                            $remainingMinutes = max(1, now()->diffInMinutes($lockedPending->expires_at, false));
                            $lockedPending->update(['reminder_sent_at' => now()]);
                            $this->notifications->notifyCustomerPendingCartReminder($lockedPending, $remainingMinutes);
                            $this->notifications->notifyAdminPendingCartReminder($lockedPending);
                            $summary['reminded']++;
                        }
                    });
                }
            });

        return $summary;
    }

    public function convert(PendingOrder $pending, Order $order): void
    {
        $pending->update([
            'status' => PendingOrder::STATUS_CONVERTED,
            'converted_order_id' => $order->id,
            'closed_at' => now(),
            'close_reason' => null,
        ]);
    }

    public function syncSnapshots(PendingOrder $pending, Cart $cart): void
    {
        $cart->loadMissing('items.productVariant.product');
        $activeCartItemIds = $cart->items->pluck('id')->all();

        foreach ($cart->items as $item) {
            PendingOrderItem::query()->updateOrCreate(
                [
                    'pending_order_id' => $pending->id,
                    'cart_item_id' => $item->id,
                ],
                [
                    'product_id' => $item->productVariant?->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name_snapshot' => $item->product_name_snapshot,
                    'variant_name_snapshot' => $item->variant_name_snapshot,
                    'sku_snapshot' => $item->sku_snapshot,
                    'quantity' => $item->quantity,
                    'price_snapshot' => $item->unit_price,
                    'sale_type' => $item->sale_type,
                    'daily_offer_id' => $item->daily_offer_id,
                    'added_at' => PendingOrderItem::query()
                        ->where('pending_order_id', $pending->id)
                        ->where('cart_item_id', $item->id)
                        ->value('added_at') ?? now(),
                    'removed_at' => null,
                ]
            );
        }

        PendingOrderItem::query()
            ->where('pending_order_id', $pending->id)
            ->whereNull('removed_at')
            ->whereNotIn('cart_item_id', $activeCartItemIds)
            ->update(['removed_at' => now()]);
    }

    public function close(PendingOrder $pending, string $reason): void
    {
        $pending->update([
            'status' => PendingOrder::STATUS_NOT_ORDERED,
            'closed_at' => now(),
            'close_reason' => $reason,
        ]);
    }

    private function expireLocked(PendingOrder $pending, Cart $cart): void
    {
        if ($pending->status !== PendingOrder::STATUS_ACTIVE || $pending->expires_at->isFuture()) {
            return;
        }

        CartItem::query()
            ->where('cart_id', $cart->id)
            ->delete();

        PendingOrderItem::query()
            ->where('pending_order_id', $pending->id)
            ->whereNull('removed_at')
            ->update(['removed_at' => now()]);

        $this->close($pending, PendingOrder::CLOSE_EXPIRED);
        $cart->increment('revision');
        $this->notifications->notifyCustomerCartExpired($pending);
    }

    public function generateReference(): string
    {
        return 'PND-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function createActivePendingWithRetry(Cart $cart): PendingOrder
    {
        $attempts = 0;

        while ($attempts < 5) {
            $attempts++;

            try {
                return PendingOrder::query()->create([
                    'customer_id' => $cart->customer_id,
                    'cart_id' => $cart->id,
                    'reference' => $this->generateReference(),
                    'status' => PendingOrder::STATUS_ACTIVE,
                    'started_at' => now(),
                    'expires_at' => now()->addMinutes($this->holdMinutes()),
                ]);
            } catch (QueryException $exception) {
                if ($this->isUniqueViolation($exception, 'pending_orders_one_active_per_cart_unique')) {
                    $pending = PendingOrder::query()
                        ->active()
                        ->where('cart_id', $cart->id)
                        ->lockForUpdate()
                        ->first();

                    if ($pending) {
                        return $pending;
                    }
                }

                if ($this->isUniqueViolation($exception, 'pending_orders_reference_unique') && $attempts < 5) {
                    continue;
                }

                throw $exception;
            }
        }

        throw new \RuntimeException('Unable to create a unique pending order reference.');
    }

    private function isUniqueViolation(QueryException $exception, string $indexName): bool
    {
        return ($exception->errorInfo[0] ?? null) === '23000'
            && str_contains($exception->getMessage(), $indexName);
    }
}
