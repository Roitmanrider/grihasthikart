<?php

namespace App\Domains\Order\Services;

use App\Models\Order;

class OrderStatusService
{
    public const PIPELINE = ['placed', 'confirmed', 'picking', 'packed', 'out_for_delivery', 'delivered'];

    public const CANCELLATION_STATUSES = ['cancelled', 'cancelled_by_admin', 'cancelled_by_customer'];

    private const ALLOWED_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled_by_admin', 'cancelled_by_customer', 'cancelled'],
        'placed' => ['confirmed', 'cancelled_by_admin', 'cancelled_by_customer', 'cancelled'],
        'confirmed' => ['picking', 'preparing', 'cancelled_by_admin', 'cancelled_by_customer', 'cancelled'],
        'picking' => ['packed', 'cancelled_by_admin', 'cancelled'],
        'preparing' => ['packed', 'ready_for_delivery', 'cancelled_by_admin', 'cancelled'],
        'packed' => ['out_for_delivery', 'cancelled_by_admin', 'cancelled'],
        'ready_for_delivery' => ['out_for_delivery', 'delivered', 'cancelled_by_admin', 'cancelled'],
        'out_for_delivery' => ['delivered', 'cancelled_by_admin', 'cancelled'],
        'delivered' => ['returned'],
        'cancelled' => [],
        'cancelled_by_admin' => [],
        'cancelled_by_customer' => [],
        'returned' => [],
    ];

    private const ACTIONS = [
        'placed' => [
            ['status' => 'confirmed', 'label' => 'Confirm Order', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'confirmed' => [
            ['status' => 'picking', 'label' => 'Start Picking', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'picking' => [
            ['status' => 'packed', 'label' => 'Mark Packed', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'preparing' => [
            ['status' => 'packed', 'label' => 'Mark Packed', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'packed' => [
            ['status' => 'out_for_delivery', 'label' => 'Out for Delivery', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'ready_for_delivery' => [
            ['status' => 'out_for_delivery', 'label' => 'Out for Delivery', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'out_for_delivery' => [
            ['status' => 'delivered', 'label' => 'Mark Delivered', 'class' => 'btn-success'],
            ['status' => 'cancelled_by_admin', 'label' => 'Cancel Order', 'class' => 'btn-outline-danger'],
        ],
        'delivered' => [
            ['status' => 'returned', 'label' => 'Mark Returned', 'class' => 'btn-outline-warning'],
        ],
    ];

    private const CUSTOMER_CANCELLABLE_STATUSES = ['pending', 'placed', 'confirmed'];

    private const LABELS = [
        'pending' => 'Placed',
        'placed' => 'Placed',
        'confirmed' => 'Confirmed',
        'picking' => 'Picking',
        'preparing' => 'Picking',
        'packed' => 'Packed',
        'ready_for_delivery' => 'Out for Delivery',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'cancelled_by_admin' => 'Cancelled by Admin',
        'cancelled_by_customer' => 'Cancelled by Customer',
        'returned' => 'Returned',
    ];

    public function validStatuses(): array
    {
        return Order::STATUSES;
    }

    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        return in_array($toStatus, self::ALLOWED_TRANSITIONS[$fromStatus] ?? [], true)
            && in_array($toStatus, $this->validStatuses(), true);
    }

    public function actionsFor(Order $order): array
    {
        return collect(self::ACTIONS[$order->order_status] ?? [])
            ->filter(fn (array $action) => $this->canTransition($order->order_status, $action['status']))
            ->when(! in_array('returned', $this->validStatuses(), true), function ($actions) {
                return $actions->reject(fn (array $action) => $action['status'] === 'returned');
            })
            ->values()
            ->all();
    }

    public function canCustomerCancel(Order $order): bool
    {
        return in_array($order->order_status, self::CUSTOMER_CANCELLABLE_STATUSES, true)
            && $this->canTransition($order->order_status, 'cancelled_by_customer');
    }

    public function timelineFor(Order $order): array
    {
        $currentPosition = $this->pipelinePosition($order->order_status);
        $isFinalException = $this->isCancellation($order->order_status) || $order->order_status === 'returned';

        $steps = collect(self::PIPELINE)->map(function (string $status, int $position) use ($currentPosition, $order) {
            return [
                'status' => $status,
                'label' => $this->label($status),
                'state' => $position < $currentPosition ? 'completed' : ($position === $currentPosition ? 'current' : 'upcoming'),
                'completed_at' => $this->completedAt($order, $status),
            ];
        })->all();

        return [
            'steps' => $steps,
            'final_state' => $isFinalException ? $this->label($order->order_status) : null,
        ];
    }

    public function label(string $status): string
    {
        return self::LABELS[$status] ?? str($status)->replace('_', ' ')->headline()->toString();
    }

    public function isCancellation(string $status): bool
    {
        return in_array($status, self::CANCELLATION_STATUSES, true);
    }

    public function cancelledStatuses(): array
    {
        return self::CANCELLATION_STATUSES;
    }

    private function pipelinePosition(string $status): int
    {
        $normalized = match ($status) {
            'pending' => 'placed',
            'preparing' => 'picking',
            'ready_for_delivery' => 'out_for_delivery',
            default => $status,
        };

        $position = array_search($normalized, self::PIPELINE, true);

        return $position === false ? -1 : $position;
    }

    private function completedAt(Order $order, string $status): ?string
    {
        return match ($status) {
            'placed' => $order->placed_at?->format('d M Y, h:i A'),
            'confirmed' => $order->confirmed_at?->format('d M Y, h:i A'),
            'delivered' => $order->delivered_at?->format('d M Y, h:i A'),
            default => null,
        };
    }
}
