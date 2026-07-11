<?php

namespace App\Domains\Notification\Services;

use App\Domains\Order\Services\OrderStatusService;
use App\Models\CashbackRedemptionRequest;
use App\Models\ContactMessage;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;

class NotificationService
{
    public function __construct(
        private readonly OrderStatusService $orderStatusService
    ) {}

    public function adminUnreadCount(): int
    {
        return Notification::query()->admin()->unread()->count();
    }

    public function customerUnreadCount(Customer $customer): int
    {
        return Notification::query()->forCustomer($customer)->unread()->count();
    }

    public function notifyAdminNewOrder(Order $order): void
    {
        $this->admin(
            'order.placed',
            'New order placed',
            'Order '.$order->order_number.' has been placed for Rs. '.number_format((float) $order->grand_total, 2).'.',
            route('admin.orders.show', $order),
            $order,
            ['order_number' => $order->order_number]
        );
    }

    public function notifyAdminCustomerCancelledOrder(Order $order, ?string $reason = null): void
    {
        $this->admin(
            'order.cancelled_by_customer',
            'Order cancelled by customer',
            trim('Order '.$order->order_number.' was cancelled by the customer.'.($reason ? ' Reason: '.$reason : '')),
            route('admin.orders.show', $order),
            $order,
            ['order_number' => $order->order_number, 'reason' => $reason]
        );
    }

    public function notifyCustomerOrderStatusChanged(Order $order, string $newStatus, ?string $note = null): void
    {
        if (! $order->customer_id) {
            return;
        }

        if ($newStatus === 'cancelled_by_customer') {
            return;
        }

        if ($newStatus === 'cancelled_by_admin') {
            $this->customer(
                $order->customer,
                'order.cancelled_by_admin',
                'Order cancelled',
                trim('Order '.$order->order_number.' was cancelled by admin.'.($note ? ' Reason: '.$note : '')),
                route('customer.orders.show', $order->order_number),
                $order,
                ['order_number' => $order->order_number, 'reason' => $note]
            );

            return;
        }

        if (! in_array($newStatus, ['confirmed', 'picking', 'packed', 'out_for_delivery', 'delivered'], true)) {
            return;
        }

        $label = $this->orderStatusService->label($newStatus);

        $this->customer(
            $order->customer,
            'order.status_changed',
            'Order '.$label,
            'Order '.$order->order_number.' is now '.$label.'.',
            route('customer.orders.show', $order->order_number),
            $order,
            ['order_number' => $order->order_number, 'status' => $newStatus]
        );
    }

    public function notifyRazorpayPaymentSuccess(Order $order, Payment $payment): void
    {
        if ($order->customer_id) {
            $this->customer(
                $order->customer,
                'payment.razorpay_success',
                'Payment successful',
                'Online payment for order '.$order->order_number.' was successful.',
                route('customer.orders.show', $order->order_number),
                $payment,
                ['order_number' => $order->order_number, 'payment_number' => $payment->payment_number]
            );
        }
    }

    public function notifyRazorpayPaymentFailed(Order $order, Payment $payment, ?string $reason = null): void
    {
        $message = trim('Online payment failed for order '.$order->order_number.'.'.($reason ? ' Reason: '.$reason : ''));

        $this->admin(
            'payment.razorpay_failed',
            'Razorpay payment failed',
            $message,
            route('admin.payments.show', $payment),
            $payment,
            ['order_number' => $order->order_number, 'payment_number' => $payment->payment_number, 'reason' => $reason]
        );

        if ($order->customer_id) {
            $this->customer(
                $order->customer,
                'payment.razorpay_failed',
                'Payment failed',
                $message,
                route('customer.orders.show', $order->order_number),
                $payment,
                ['order_number' => $order->order_number, 'payment_number' => $payment->payment_number, 'reason' => $reason]
            );
        }
    }

    public function notifyAdminNewContactMessage(ContactMessage $message): void
    {
        $this->admin(
            'contact.message_created',
            'New contact message',
            'New message from '.$message->name.'.',
            route('admin.contact-messages.index'),
            $message,
            ['contact_message_id' => $message->id]
        );
    }

    public function notifyAdminLowStock(Inventory $inventory): void
    {
        if (! $inventory->is_low_stock) {
            return;
        }

        $existingUnread = Notification::query()
            ->admin()
            ->unread()
            ->where('type', 'inventory.low_stock')
            ->where('notifiable_type', Inventory::class)
            ->where('notifiable_id', $inventory->id)
            ->exists();

        if ($existingUnread) {
            return;
        }

        $variant = $inventory->productVariant;
        $productName = $variant?->product?->name ?? 'Inventory item';
        $variantName = $variant?->variant_name ? ' / '.$variant->variant_name : '';

        $this->admin(
            'inventory.low_stock',
            'Low stock item',
            $productName.$variantName.' is at '.$inventory->available_quantity.' available stock.',
            route('admin.inventories.show', $inventory),
            $inventory,
            ['inventory_id' => $inventory->id, 'available_quantity' => $inventory->available_quantity]
        );
    }

    public function notifyAdminCashbackRedemptionRequested(CashbackRedemptionRequest $redemption): void
    {
        $this->admin(
            'cashback.redemption_requested',
            'Cashback redemption requested',
            'Customer requested cashback redemption of Rs. '.number_format((float) $redemption->requested_amount, 2).'.',
            route('admin.cashback.redemptions.show', $redemption),
            $redemption,
            ['redemption_id' => $redemption->id]
        );
    }

    public function notifyCustomerCashbackUpdated(CashbackRedemptionRequest $redemption, string $title, string $message): void
    {
        $this->customer(
            $redemption->customer,
            'cashback.updated',
            $title,
            $message,
            route('customer.cashback.index'),
            $redemption,
            ['redemption_id' => $redemption->id]
        );
    }

    private function admin(string $type, string $title, ?string $message, ?string $actionUrl = null, ?object $notifiable = null, array $data = []): Notification
    {
        return $this->create(Notification::AUDIENCE_ADMIN, null, $type, $title, $message, $actionUrl, $notifiable, $data);
    }

    private function customer(Customer $customer, string $type, string $title, ?string $message, ?string $actionUrl = null, ?object $notifiable = null, array $data = []): Notification
    {
        return $this->create(Notification::AUDIENCE_CUSTOMER, $customer, $type, $title, $message, $actionUrl, $notifiable, $data);
    }

    private function create(string $audience, ?Customer $customer, string $type, string $title, ?string $message, ?string $actionUrl, ?object $notifiable, array $data): Notification
    {
        return Notification::query()->create([
            'notifiable_type' => $notifiable ? $notifiable::class : null,
            'notifiable_id' => $notifiable?->id,
            'audience' => $audience,
            'customer_id' => $customer?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data ?: null,
        ]);
    }
}
