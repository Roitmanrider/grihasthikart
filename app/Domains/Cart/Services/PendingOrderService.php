<?php

namespace App\Domains\Cart\Services;

use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Messaging\Contracts\WhatsAppMessagingServiceInterface;
use App\Domains\Notification\Services\NotificationService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\PendingOrder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PendingOrderService
{
    public function __construct(
        private readonly BusinessSettingService $settings,
        private readonly NotificationService $notifications,
        private readonly InventoryService $inventoryService,
        private readonly WhatsAppMessagingServiceInterface $whatsApp
    ) {}

    public function holdMinutes(): int
    {
        return max(1, (int) $this->settings->get('checkout.cart_hold_minutes', 60));
    }

    public function reminderMinutes(): int
    {
        return max(1, (int) $this->settings->get('checkout.cart_reminder_minutes', 30));
    }

    public function whatsAppReminderMinutes(): int
    {
        return max(1, (int) $this->settings->get('checkout.cart_whatsapp_reminder_minutes', 45));
    }

    public function activeForCart(Cart $cart): ?PendingOrder
    {
        if (! $cart->customer_id) {
            return null;
        }

        return PendingOrder::query()
            ->active()
            ->where('cart_id', $cart->id)
            ->with(['cart.items.productVariant.inventories', 'customer'])
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
        }

        $this->syncActivity($pending, $cart);
        $this->syncReservedStock($pending, $cart);

        return $pending->fresh(['cart.items.productVariant.inventories', 'customer']);
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

        if ($cart->items->isEmpty()) {
            $this->releaseReservedStock($pending, 'Cart cleared');
            $this->close($pending, PendingOrder::CLOSE_CART_CLEARED);

            return;
        }

        $this->syncActivity($pending, $cart, $removedItem);
        $this->syncReservedStock($pending, $cart);
    }

    public function afterCartCleared(Cart $cart): void
    {
        $pending = $this->activeForCart($cart);

        if (! $pending) {
            return;
        }

        $this->releaseReservedStock($pending, 'Cart cleared');
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

        if (! $pending || $pending->expires_at->isPast()) {
            return;
        }

        $this->sendInAppReminderIfDue($pending, $cart);
        $this->markFollowUpEligibleIfDue($pending, $cart);
        $this->sendWhatsAppReminderIfDue($pending, $cart);
    }

    public function processDue(int $chunkSize = 100): array
    {
        $summary = ['reminded' => 0, 'follow_up_eligible' => 0, 'whatsapp_sent' => 0, 'whatsapp_skipped' => 0, 'expired' => 0];

        PendingOrder::query()
            ->active()
            ->where(function ($query) {
                $query->where('expires_at', '<=', now())
                    ->orWhere(function ($query) {
                        $query->whereNull('reminder_sent_at')
                            ->where('started_at', '<=', now()->subMinutes($this->reminderMinutes()));
                    })
                    ->orWhere(function ($query) {
                        $query->whereNull('follow_up_eligible_at')
                            ->where('started_at', '<=', now()->subMinutes($this->reminderMinutes()));
                    })
                    ->orWhere(function ($query) {
                        $query->whereNull('whatsapp_reminder_attempted_at')
                            ->where('whatsapp_reminder_due_at', '<=', now());
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

                        if ($this->sendInAppReminderIfDue($lockedPending, $cart)) {
                            $summary['reminded']++;
                        }

                        if ($this->markFollowUpEligibleIfDue($lockedPending, $cart)) {
                            $summary['follow_up_eligible']++;
                        }

                        $whatsAppResult = $this->sendWhatsAppReminderIfDue($lockedPending, $cart);

                        if ($whatsAppResult === 'sent') {
                            $summary['whatsapp_sent']++;
                        } elseif ($whatsAppResult === 'skipped') {
                            $summary['whatsapp_skipped']++;
                        }
                    });
                }
            });

        return $summary;
    }

    public function convert(PendingOrder $pending, Order $order): void
    {
        $this->releaseReservedStock($pending, 'Converted to order '.$order->order_number);

        $pending->update([
            'status' => PendingOrder::STATUS_CONVERTED,
            'converted_order_id' => $order->id,
            'closed_at' => now(),
            'close_reason' => null,
            'detail_cleanup_eligible_at' => now()->addDays(7),
        ]);
    }

    public function syncActivity(PendingOrder $pending, Cart $cart, ?CartItem $removedItem = null): void
    {
        $cart->loadMissing('items.productVariant.inventories');
        $anchorId = $pending->anchor_cart_item_id;
        $nextAnchor = $cart->items->sortBy('id')->first();
        $anchorChanged = false;

        if (! $anchorId && $nextAnchor) {
            $anchorId = $nextAnchor->id;
        } elseif ($removedItem && $pending->anchor_cart_item_id === $removedItem->id) {
            $anchorId = $nextAnchor?->id;
            $anchorChanged = true;
        }

        $updates = [
            'anchor_cart_item_id' => $anchorId,
            'last_activity_at' => now(),
            'follow_up_eligible_at' => $pending->follow_up_eligible_at ?: $this->followUpEligibleAtIfDue($pending, $cart),
            'whatsapp_reminder_due_at' => $pending->whatsapp_reminder_due_at ?? $this->whatsAppReminderDueAt(),
            'cart_value_snapshot' => $cart->items->sum(fn (CartItem $item) => (float) $item->quantity * (float) $item->unit_price),
            'item_count_snapshot' => (int) $cart->items->sum('quantity'),
            'reserved_sku_count_snapshot' => $cart->items->pluck('product_variant_id')->unique()->count(),
            'scarce_stock_hold' => $this->hasScarceStockHold($cart),
            'risk_level' => $this->settings->get('checkout.cart_abuse_monitoring_enabled', true) ? $this->activityRiskLevel($pending, $cart) : 'NORMAL',
        ];

        if ($anchorChanged) {
            $updates['anchor_changed_at'] = now();
            $updates['anchor_change_count'] = ((int) $pending->anchor_change_count) + 1;
        }

        $pending->update($updates);
    }

    public function close(PendingOrder $pending, string $reason): void
    {
        $this->releaseReservedStock($pending, str_replace('_', ' ', strtolower($reason)));

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

        $this->releaseReservedStock($pending, 'Cart expired');

        CartItem::query()
            ->where('cart_id', $cart->id)
            ->delete();

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
                    'stock_location_id' => $cart->stock_location_id,
                    'reference' => $this->generateReference(),
                    'status' => PendingOrder::STATUS_ACTIVE,
                    'started_at' => now(),
                    'last_activity_at' => now(),
                    'expires_at' => now()->addMinutes($this->holdMinutes()),
                    'whatsapp_reminder_due_at' => $this->whatsAppReminderDueAt(),
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

    public function releaseReservedStock(PendingOrder $pending, string $note): void
    {
        foreach ($this->reservedAllocations($pending) as $allocation) {
            $quantity = (float) $allocation->reserved_quantity;

            if ($quantity <= 0) {
                continue;
            }

            $this->inventoryService->releaseReservedStock(
                (int) $allocation->product_variant_id,
                (int) $allocation->stock_location_id,
                $quantity,
                $note,
                PendingOrder::class,
                $pending->id
            );
        }
    }

    private function syncReservedStock(PendingOrder $pending, Cart $cart): void
    {
        $desiredByVariant = $cart->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (float) $items->sum('quantity'));
        $allocations = $this->reservedAllocations($pending)
            ->groupBy('product_variant_id');

        foreach ($allocations as $variantId => $variantAllocations) {
            $desired = (float) ($desiredByVariant[$variantId] ?? 0);
            $current = (float) $variantAllocations->sum('reserved_quantity');

            if ($current > $desired) {
                $this->releaseVariantReservation($pending, (int) $variantId, $current - $desired, $variantAllocations);
            }
        }

        foreach ($desiredByVariant as $variantId => $desired) {
            $current = (float) ($allocations[(int) $variantId] ?? collect())->sum('reserved_quantity');

            if ($desired > $current) {
                $this->reserveVariantStock($pending, (int) $variantId, $desired - $current);
            }
        }
    }

    private function reserveVariantStock(PendingOrder $pending, int $productVariantId, float $quantity): void
    {
        $remaining = $quantity;
        $inventories = Inventory::query()
            ->active()
            ->where('product_variant_id', $productVariantId)
            ->when($pending->stock_location_id !== null, fn ($query) => $query->where('stock_location_id', $pending->stock_location_id))
            ->orderBy('stock_location_id')
            ->get();

        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $reserve = min($remaining, (float) $inventory->available_quantity);

            if ($reserve <= 0) {
                continue;
            }

            $this->inventoryService->reserveStock(
                $productVariantId,
                $inventory->stock_location_id,
                $reserve,
                'Cart '.$pending->reference.' reservation',
                PendingOrder::class,
                $pending->id
            );
            $remaining -= $reserve;
        }
    }

    private function releaseVariantReservation(PendingOrder $pending, int $productVariantId, float $quantity, $allocations): void
    {
        $remaining = $quantity;

        foreach ($allocations->sortByDesc('stock_location_id') as $allocation) {
            if ($remaining <= 0) {
                break;
            }

            $release = min($remaining, (float) $allocation->reserved_quantity);

            if ($release <= 0) {
                continue;
            }

            $this->inventoryService->releaseReservedStock(
                $productVariantId,
                (int) $allocation->stock_location_id,
                $release,
                'Cart '.$pending->reference.' reservation adjusted',
                PendingOrder::class,
                $pending->id
            );
            $remaining -= $release;
        }
    }

    private function reservedAllocations(PendingOrder $pending)
    {
        return InventoryMovement::query()
            ->selectRaw("product_variant_id, stock_location_id, SUM(CASE WHEN movement_type = 'reservation' THEN quantity ELSE -quantity END) as reserved_quantity")
            ->where('reference_type', PendingOrder::class)
            ->where('reference_id', $pending->id)
            ->whereIn('movement_type', ['reservation', 'reservation_release'])
            ->groupBy('product_variant_id', 'stock_location_id')
            ->havingRaw('reserved_quantity > 0')
            ->get();
    }

    private function sendInAppReminderIfDue(PendingOrder $pending, Cart $cart): bool
    {
        if (! $this->settings->get('checkout.cart_reminder_enabled', true)
            || $pending->reminder_sent_at
            || $pending->started_at->copy()->addMinutes($this->reminderMinutes())->isFuture()
            || $cart->items()->count() === 0) {
            return false;
        }

        $remainingMinutes = max(1, now()->diffInMinutes($pending->expires_at, false));
        $pending->update([
            'reminder_sent_at' => now(),
            'follow_up_eligible_at' => $pending->follow_up_eligible_at ?? now(),
        ]);
        $this->notifications->notifyCustomerPendingCartReminder($pending, $remainingMinutes);

        return true;
    }

    private function markFollowUpEligibleIfDue(PendingOrder $pending, Cart $cart): bool
    {
        if ($pending->follow_up_eligible_at
            || ! $this->settings->get('checkout.cart_employee_followup_enabled', true)
            || $pending->started_at->copy()->addMinutes($this->reminderMinutes())->isFuture()
            || $cart->items()->count() === 0) {
            return false;
        }

        $pending->update(['follow_up_eligible_at' => now()]);

        return true;
    }

    private function followUpEligibleAtIfDue(PendingOrder $pending, Cart $cart): ?Carbon
    {
        if (! $this->settings->get('checkout.cart_employee_followup_enabled', true)
            || $pending->started_at->copy()->addMinutes($this->reminderMinutes())->isFuture()
            || $cart->items->isEmpty()) {
            return null;
        }

        return now();
    }

    private function sendWhatsAppReminderIfDue(PendingOrder $pending, Cart $cart): ?string
    {
        if (! $this->settings->get('checkout.cart_whatsapp_reminder_enabled', false)
            || $pending->whatsapp_reminder_attempted_at
            || ! $pending->whatsapp_reminder_due_at
            || $pending->whatsapp_reminder_due_at->isFuture()
            || $cart->items()->count() === 0) {
            return null;
        }

        $attemptedAt = now();

        if (! $this->whatsApp->configured()) {
            $pending->update([
                'whatsapp_reminder_attempted_at' => $attemptedAt,
                'whatsapp_reminder_status' => 'NOT_CONFIGURED',
                'whatsapp_failure_code' => 'NOT_CONFIGURED',
                'whatsapp_failure_message' => 'WhatsApp messaging provider is not configured.',
            ]);

            return 'skipped';
        }

        $remainingMinutes = max(1, now()->diffInMinutes($pending->expires_at, false));
        $result = $this->whatsApp->sendCartReminder($pending, $remainingMinutes);

        $pending->update([
            'whatsapp_reminder_attempted_at' => $attemptedAt,
            'whatsapp_reminder_status' => $result->sent ? 'SENT' : 'FAILED',
            'whatsapp_provider_message_id' => $result->providerMessageId,
            'whatsapp_failure_code' => $result->failureCode,
            'whatsapp_failure_message' => $result->failureMessage,
        ]);

        return $result->sent ? 'sent' : 'skipped';
    }

    private function whatsAppReminderDueAt(): ?Carbon
    {
        if (! $this->settings->get('checkout.cart_whatsapp_reminder_enabled', false)) {
            return null;
        }

        return now()->addMinutes($this->whatsAppReminderMinutes());
    }

    private function hasScarceStockHold(Cart $cart): bool
    {
        foreach ($cart->items as $item) {
            $variant = $item->productVariant;

            if (! $variant) {
                continue;
            }

            foreach ($variant->inventories as $inventory) {
                $threshold = $inventory->low_stock_threshold;
                $available = max(0.001, (float) $inventory->available_quantity);

                if ($threshold !== null
                    && $available <= (float) $threshold
                    && ((float) $item->quantity / $available) >= 0.70) {
                    return true;
                }
            }
        }

        return false;
    }

    private function activityRiskLevel(PendingOrder $pending, Cart $cart): string
    {
        if ($this->hasScarceStockHold($cart) || (int) $pending->anchor_change_count >= 3) {
            return 'WATCH';
        }

        return 'NORMAL';
    }
}
