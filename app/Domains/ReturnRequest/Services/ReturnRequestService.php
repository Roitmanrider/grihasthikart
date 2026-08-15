<?php

namespace App\Domains\ReturnRequest\Services;

use App\Domains\Customer\Services\CustomerCreditService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Notification\Services\NotificationService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ReturnRequest;
use App\Models\StockLocation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ReturnRequestService
{
    public function __construct(
        private readonly BusinessSettingService $settings,
        private readonly InventoryService $inventoryService,
        private readonly NotificationService $notificationService,
        private readonly CustomerCreditService $customerCreditService
    ) {}

    public function customerPaginate(Customer $customer, int $perPage = 20)
    {
        return ReturnRequest::query()
            ->where('customer_id', $customer->id)
            ->with('order')
            ->latest('requested_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function adminPaginate(int $perPage = 20)
    {
        return ReturnRequest::query()
            ->with(['order', 'customer'])
            ->latest('requested_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function isEligible(Order $order): bool
    {
        return $order->customer_id !== null
            && $order->order_status === 'delivered'
            && $order->delivered_at !== null
            && $this->returnAvailableUntil($order)?->gte(now());
    }

    public function returnAvailableUntil(Order $order): ?Carbon
    {
        if ($order->delivered_at === null) {
            return null;
        }

        return $order->delivered_at->copy()->addDays($this->returnWindowDays())->endOfDay();
    }

    public function returnWindowDays(): int
    {
        return (int) $this->settings->get('order.return_window_days', 2);
    }

    public function remainingReturnableQuantity(OrderItem $item): float
    {
        $alreadyRequested = DB::table('return_request_items')
            ->join('return_requests', 'return_requests.id', '=', 'return_request_items.return_request_id')
            ->where('return_request_items.order_item_id', $item->id)
            ->whereIn('return_requests.status', ReturnRequest::QUANTITY_HOLDING_STATUSES)
            ->sum('return_request_items.quantity');

        return max(0, (float) $item->quantity - (float) $alreadyRequested);
    }

    public function create(Customer $customer, array $data): ReturnRequest
    {
        return DB::transaction(function () use ($customer, $data) {
            $order = Order::query()
                ->where('customer_id', $customer->id)
                ->with('items')
                ->lockForUpdate()
                ->findOrFail($data['order_id']);

            if (! $this->isEligible($order)) {
                throw new InvalidArgumentException('This order is not eligible for return.');
            }

            $items = $this->normalizedItems($order, $data['items'] ?? []);

            if ($items === []) {
                throw new InvalidArgumentException('Select at least one item to return.');
            }

            $return = ReturnRequest::query()->create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'return_number' => $this->generateReturnNumber(),
                'status' => 'requested',
                'reason' => $data['reason'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
                'requested_at' => now(),
                'refund_amount' => round(collect($items)->sum('refund_amount'), 2),
            ]);

            foreach ($items as $item) {
                $return->items()->create($item);
            }

            $return = $return->fresh(['order', 'customer', 'items.orderItem']);
            $this->notificationService->notifyAdminReturnRequested($return);

            return $return;
        });
    }

    public function approve(ReturnRequest $returnRequest, ?string $notes = null, bool $restock = false): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequest, $notes, $restock) {
            $return = ReturnRequest::query()
                ->with(['items.orderItem', 'customer'])
                ->whereKey($returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($return->status !== 'requested') {
                throw new InvalidArgumentException('Only requested returns can be approved.');
            }

            $return->update([
                'status' => 'approved',
                'admin_notes' => $notes,
                'approved_at' => now(),
                'restock_items' => $restock,
            ]);

            if ($restock) {
                $this->restock($return);
            }

            $return = $return->fresh(['order', 'customer', 'items.orderItem']);
            $this->notificationService->notifyCustomerReturnUpdated($return, 'Return approved', 'Your return request '.$return->return_number.' was approved.');

            return $return;
        });
    }

    public function reject(ReturnRequest $returnRequest, string $notes): ReturnRequest
    {
        $this->ensureStatus($returnRequest, ['requested', 'approved'], 'Only requested or approved returns can be rejected.');
        $returnRequest->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
            'rejected_at' => now(),
        ]);

        $return = $returnRequest->fresh(['order', 'customer']);
        $this->notificationService->notifyCustomerReturnUpdated($return, 'Return rejected', 'Your return request '.$return->return_number.' was rejected. Reason: '.$notes);

        return $return;
    }

    public function markRefunded(ReturnRequest $returnRequest, ?string $notes = null): ReturnRequest
    {
        return DB::transaction(function () use ($returnRequest, $notes) {
            $return = ReturnRequest::query()
                ->whereKey($returnRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureStatus($return, ['approved', 'refunded'], 'Only approved returns can be marked refunded.');

            $this->customerCreditService->creditForReturn($return, $notes);

            if ($return->status !== 'refunded') {
                $return->update([
                    'status' => 'refunded',
                    'admin_notes' => $notes ?? $return->admin_notes,
                ]);
            }

            $return = $return->fresh(['order', 'customer']);
            $this->notificationService->notifyCustomerReturnUpdated($return, 'Return refunded', 'Your return request '.$return->return_number.' was refunded to Customer Credit.');

            return $return;
        });
    }

    public function close(ReturnRequest $returnRequest, ?string $notes = null): ReturnRequest
    {
        if ($returnRequest->status === 'closed') {
            throw new InvalidArgumentException('Return request is already closed.');
        }

        $returnRequest->update([
            'status' => 'closed',
            'admin_notes' => $notes ?? $returnRequest->admin_notes,
            'closed_at' => now(),
        ]);

        $return = $returnRequest->fresh(['order', 'customer']);
        $this->notificationService->notifyCustomerReturnUpdated($return, 'Return closed', 'Your return request '.$return->return_number.' was closed.');

        return $return;
    }

    private function normalizedItems(Order $order, array $items): array
    {
        $requestedInThisReturn = [];

        return collect($items)
            ->filter(fn (array $item) => isset($item['quantity']) && (float) $item['quantity'] > 0)
            ->map(function (array $item) use ($order, &$requestedInThisReturn): array {
                $orderItem = $order->items->firstWhere('id', (int) ($item['order_item_id'] ?? 0));

                if (! $orderItem) {
                    throw new InvalidArgumentException('Selected return item does not belong to this order.');
                }

                $quantity = (float) $item['quantity'];
                $remaining = $this->remainingReturnableQuantity($orderItem);
                $alreadyInThisReturn = $requestedInThisReturn[$orderItem->id] ?? 0.0;

                if ($quantity + $alreadyInThisReturn > $remaining) {
                    throw new InvalidArgumentException('Return quantity exceeds remaining returnable quantity for '.$orderItem->product_name_snapshot.'.');
                }

                $requestedInThisReturn[$orderItem->id] = $alreadyInThisReturn + $quantity;
                $lineShare = (float) $orderItem->line_total > 0
                    ? ((float) $orderItem->line_total / max(0.001, (float) $orderItem->quantity))
                    : (float) $orderItem->unit_price;
                $refundAmount = round($quantity * $lineShare, 2);

                return [
                    'order_item_id' => $orderItem->id,
                    'product_variant_id' => $orderItem->product_variant_id,
                    'quantity' => $quantity,
                    'unit_price' => (float) $orderItem->unit_price,
                    'refund_amount' => $refundAmount,
                    'reason' => $item['reason'] ?? null,
                    'condition' => $item['condition'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    private function restock(ReturnRequest $return): void
    {
        foreach ($return->items as $item) {
            if (! $item->product_variant_id || (float) $item->quantity <= 0) {
                continue;
            }

            $inventory = $this->inventoryForVariant((int) $item->product_variant_id);
            $this->inventoryService->adjustStock(
                $inventory,
                'return_in',
                (float) $item->quantity,
                'Return '.$return->return_number,
                ReturnRequest::class,
                $return->id
            );
        }
    }

    private function inventoryForVariant(int $productVariantId): Inventory
    {
        $location = StockLocation::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('display_order')
            ->orderBy('id')
            ->first();

        if (! $location) {
            throw new InvalidArgumentException('Create an active stock location before restocking returns.');
        }

        return Inventory::query()->firstOrCreate(
            [
                'product_variant_id' => $productVariantId,
                'stock_location_id' => $location->id,
            ],
            [
                'quantity_on_hand' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'status' => true,
            ]
        );
    }

    private function ensureStatus(ReturnRequest $returnRequest, array $statuses, string $message): void
    {
        if (! in_array($returnRequest->status, $statuses, true)) {
            throw new InvalidArgumentException($message);
        }
    }

    private function generateReturnNumber(): string
    {
        do {
            $number = 'RET'.now()->format('ymd').Str::upper(Str::random(5));
        } while (ReturnRequest::query()->where('return_number', $number)->exists());

        return $number;
    }
}
