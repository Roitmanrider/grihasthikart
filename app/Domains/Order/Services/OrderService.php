<?php

namespace App\Domains\Order\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Cart\Services\PendingOrderService;
use App\Domains\Checkout\Services\CheckoutRuleService;
use App\Domains\Coupon\Services\CouponService;
use App\Domains\Customer\Services\CustomerCreditService;
use App\Domains\Delivery\Services\DeliveryChargeService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Notification\Services\NotificationService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PendingOrder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartService $cartService,
        private readonly InventoryService $inventoryService,
        private readonly CheckoutRuleService $checkoutRuleService,
        private readonly CouponService $couponService,
        private readonly PaymentService $paymentService,
        private readonly BusinessSettingService $settingService,
        private readonly OrderStatusService $orderStatusService,
        private readonly NotificationService $notificationService,
        private readonly PendingOrderService $pendingOrderService,
        private readonly DeliveryChargeService $deliveryChargeService,
        private readonly CustomerCreditService $customerCreditService
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->orderRepository->paginatedList($filters, $perPage);
    }

    public function placeOrderFromCart(string $sessionId, array $checkoutData): Order
    {
        return DB::transaction(function () use ($sessionId, $checkoutData) {
            $cart = $this->lockedCartForSession($sessionId);
            $pending = $this->lockedActivePendingForCart($cart);

            $this->validateCartIsNotEmpty($cart);
            $this->cartService->validateDailyOfferHold($cart);
            $cart = $this->cartService->refreshCartPrices($cart);
            $this->validateCartItemsStillPurchasable($cart);
            $lockedInventories = $this->lockInventoryRowsForCart($cart);
            $this->validateInventoryAvailabilityForEveryCartItem($cart, $lockedInventories, $pending);

            $customer = isset($checkoutData['customer_id']) ? Customer::query()->find($checkoutData['customer_id']) : null;
            $couponData = $this->couponService->revalidateAppliedCoupon($cart, $customer);
            $deliveryRule = $this->deliveryChargeService->resolve($customer, $this->cartSubtotal($cart));
            $totals = $this->calculateTotalsFromCartSnapshots($cart, $couponData, $deliveryRule);
            $totals = $this->applyCustomerCredit($totals, $customer, $checkoutData);
            $this->checkoutRuleService->validateCheckout($checkoutData, $totals['subtotal'], $deliveryRule);
            $order = $this->createOrder($cart, $sessionId, $checkoutData, $totals);
            $this->createOrderItems($order, $cart);
            $this->createCouponUsageIfApplied($order, $couponData['coupon'], $couponData['discount']);
            if ((float) $order->customer_credit_used > 0 && $customer) {
                $this->customerCreditService->debitForOrder($customer, $order, (float) $order->customer_credit_used);
            }
            if ((float) $order->grand_total > 0) {
                $this->paymentService->createForOrder($order, $checkoutData['payment_method'] ?? 'cod');
            } else {
                $order->update([
                    'payment_method' => 'customer_credit',
                    'payment_status' => 'paid',
                ]);
            }
            if ($pending) {
                $this->pendingOrderService->releaseReservedStock($pending, 'Order '.$order->order_number.' placed');
                $lockedInventories = $this->lockInventoryRowsForCart($cart);
            }
            $this->deductInventoryForOrder($order, $lockedInventories);
            if ($pending) {
                $this->pendingOrderService->convert($pending, $order);
            }
            $this->createStatusHistory($order, null, 'placed', 'Order placed.');
            $this->couponService->clearCouponAfterOrder($cart);
            $this->cartService->clearCart($sessionId);
            $this->notificationService->notifyAdminNewOrder($order);

            return $order->fresh(['items', 'statusHistories', 'payment']);
        });
    }

    public function createRazorpayOrderFromCart(string $sessionId, array $checkoutData): array
    {
        if (($checkoutData['payment_method'] ?? null) !== 'razorpay') {
            throw new InvalidArgumentException('Invalid online payment method.');
        }

        return DB::transaction(function () use ($sessionId, $checkoutData) {
            $cart = $this->lockedCartForSession($sessionId);
            $this->lockedActivePendingForCart($cart);

            $this->validateCartIsNotEmpty($cart);
            $this->cartService->validateDailyOfferHold($cart);
            $cart = $this->cartService->refreshCartPrices($cart);
            $this->validateCartItemsStillPurchasable($cart);
            $this->validateInventoryAvailabilityForEveryCartItem($cart, null, $this->lockedActivePendingForCart($cart));

            $customer = isset($checkoutData['customer_id']) ? Customer::query()->find($checkoutData['customer_id']) : null;
            $couponData = $this->couponService->revalidateAppliedCoupon($cart, $customer);
            $deliveryRule = $this->deliveryChargeService->resolve($customer, $this->cartSubtotal($cart));
            $totals = $this->calculateTotalsFromCartSnapshots($cart, $couponData, $deliveryRule);
            $totals = $this->applyCustomerCredit($totals, $customer, $checkoutData);
            $this->checkoutRuleService->validateCheckout($checkoutData, $totals['subtotal'], $deliveryRule);

            if ((float) $totals['grand_total'] <= 0) {
                throw new InvalidArgumentException('This order is fully covered by Customer Credit. Please place it without online payment.');
            }

            $order = $this->createOrder($cart, $sessionId, $checkoutData, $totals, 'pending');
            $this->createOrderItems($order, $cart);
            $payment = $this->paymentService->createForOrder($order, 'razorpay');
            $this->createStatusHistory($order, null, 'pending', 'Online payment initiated.');

            return [
                'order' => $order->fresh(['items', 'payment']),
                'payment' => $payment,
                'razorpay' => [
                    'key_id' => $payment->metadata['key_id'] ?? $this->settingService->get('payment.razorpay_key_id'),
                    'order_id' => $payment->gateway_order_id,
                    'amount' => $this->paymentService->razorpayAmountToPaise($payment->amount),
                    'currency' => $payment->currency,
                ],
            ];
        });
    }

    public function retryRazorpayPayment(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->with(['items', 'payment'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_method !== 'razorpay' || $lockedOrder->payment_status === 'paid' || $lockedOrder->order_status !== 'pending') {
                throw new InvalidArgumentException('This order is not eligible for online payment retry.');
            }

            $this->validateOrderItemsStillPurchasable($lockedOrder);
            $this->validateInventoryAvailabilityForEveryOrderItem($lockedOrder);

            $payment = $this->paymentService->retryRazorpayPayment($lockedOrder);

            return [
                'order' => $lockedOrder->fresh(['items', 'payment']),
                'payment' => $payment,
                'razorpay' => [
                    'key_id' => $payment->metadata['key_id'] ?? $this->settingService->get('payment.razorpay_key_id'),
                    'order_id' => $payment->gateway_order_id,
                    'amount' => $this->paymentService->razorpayAmountToPaise($payment->amount),
                    'currency' => $payment->currency,
                ],
            ];
        });
    }

    public function completeRazorpayPayment(Order $order, array $payload): Order
    {
        return DB::transaction(function () use ($order, $payload) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->with(['items', 'payment', 'customer'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
            $payment = $lockedOrder->payment;

            if (! $payment || $payment->payment_method !== 'razorpay') {
                throw new InvalidArgumentException('Online payment record was not found.');
            }

            $this->paymentService->verifyRazorpayPayment($payment, $payload);
            $this->finalizePaidRazorpayOrder($lockedOrder, 'Online payment successful.');

            return $lockedOrder->fresh(['items', 'statusHistories', 'payment']);
        });
    }

    public function completeRazorpayPaymentFromWebhook(Payment $payment, array $providerPayment, array $eventPayload): Order
    {
        return DB::transaction(function () use ($payment, $providerPayment, $eventPayload) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()
                ->with(['items', 'payment', 'customer'])
                ->whereKey($payment->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->paymentService->markRazorpayPaidFromWebhook($lockedOrder->payment, $providerPayment, $eventPayload);
            $this->finalizePaidRazorpayOrder($lockedOrder, 'Razorpay webhook confirmed payment.');

            return $lockedOrder->fresh(['items', 'statusHistories', 'payment']);
        });
    }

    public function failRazorpayPayment(Order $order, ?string $reason = null): Order
    {
        $payment = $order->payment;

        if (! $payment || $payment->payment_method !== 'razorpay') {
            throw new InvalidArgumentException('Online payment record was not found.');
        }

        $this->paymentService->fail($payment, $reason ?: 'Customer cancelled Razorpay checkout.');

        return $order->fresh(['payment']);
    }

    public function updateOrderStatus(Order $order, string $newStatus, ?string $note = null): Order
    {
        if (! in_array($newStatus, Order::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid order status.');
        }

        return DB::transaction(function () use ($order, $newStatus, $note) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedOrder->order_status;

            if (! $this->orderStatusService->canTransition($oldStatus, $newStatus)) {
                throw new InvalidArgumentException('This order status transition is not allowed.');
            }

            if ($this->orderStatusService->isCancellation($newStatus)) {
                $this->restoreStockOnCancellation($lockedOrder);
                $this->customerCreditService->restoreForCancelledOrder($lockedOrder, $note);
                $lockedOrder->cancelled_at = now();
            }

            if ($newStatus === 'confirmed') {
                $lockedOrder->confirmed_at = now();
            }

            if ($newStatus === 'delivered') {
                $lockedOrder->delivered_at = now();
            }

            $lockedOrder->order_status = $newStatus;
            $lockedOrder->admin_notes = $note;
            $lockedOrder->save();
            $this->createStatusHistory($lockedOrder, $oldStatus, $newStatus, $note);
            $lockedOrder->refresh();

            if ($newStatus === 'cancelled_by_customer') {
                $this->notificationService->notifyAdminCustomerCancelledOrder($lockedOrder, $note);
            }

            $this->notificationService->notifyCustomerOrderStatusChanged($lockedOrder, $newStatus, $note);

            return $lockedOrder->fresh(['items', 'statusHistories']);
        });
    }

    public function cancelByCustomer(Order $order, string $reason): Order
    {
        if (! $this->orderStatusService->canCustomerCancel($order)) {
            throw new InvalidArgumentException('This order can no longer be cancelled online.');
        }

        return $this->updateOrderStatus($order, 'cancelled_by_customer', $reason);
    }

    public function validateCartIsNotEmpty(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw new InvalidArgumentException('Your cart is empty.');
        }
    }

    public function validateCartItemsStillPurchasable(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $variant = $item->productVariant?->load('product');

            if (! $variant || ! $variant->status || ! $variant->product?->status) {
                throw new InvalidArgumentException('One or more cart items are no longer available.');
            }
        }
    }

    public function validateInventoryAvailabilityForEveryCartItem(Cart $cart, ?iterable $lockedInventories = null, ?PendingOrder $pending = null): void
    {
        $inventories = $lockedInventories ? collect($lockedInventories) : null;

        foreach ($cart->items as $item) {
            $available = $inventories
                ? (float) $inventories->where('product_variant_id', $item->product_variant_id)->sum('available_quantity')
                : $this->inventoryService->getAvailableQuantity($item->product_variant_id);
            $available += $pending ? $this->pendingReservedQuantityForVariant($pending, (int) $item->product_variant_id) : 0.0;

            if ($available < (float) $item->quantity) {
                if ($available <= 0) {
                    throw new InvalidArgumentException($item->product_name_snapshot.' is no longer available. Please review your cart.');
                }

                throw new InvalidArgumentException($item->product_name_snapshot.' is now available only in quantity '.(int) floor($available).'. Please review your cart.');
            }
        }
    }

    public function validateInventoryAvailabilityForEveryOrderItem(Order $order): void
    {
        $variantIds = $order->items
            ->pluck('product_variant_id')
            ->unique()
            ->sort()
            ->values();

        $inventories = Inventory::query()
            ->active()
            ->whereIn('product_variant_id', $variantIds)
            ->when($order->stock_location_id !== null, fn ($query) => $query->where('stock_location_id', $order->stock_location_id))
            ->orderBy('product_variant_id')
            ->orderBy('stock_location_id')
            ->lockForUpdate()
            ->get();

        foreach ($order->items as $item) {
            $available = (float) $inventories
                ->where('product_variant_id', $item->product_variant_id)
                ->sum('available_quantity');

            if ($available < (float) $item->quantity) {
                if ($available <= 0) {
                    throw new InvalidArgumentException($item->product_name_snapshot.' is no longer available. Please review your order.');
                }

                throw new InvalidArgumentException($item->product_name_snapshot.' is now available only in quantity '.(int) floor($available).'. Please review your order.');
            }
        }
    }

    public function calculateTotalsFromCartSnapshots(Cart $cart, array|float $couponEffect = 0, ?array $deliveryRule = null): array
    {
        $subtotal = 0.0;
        $totalMrp = 0.0;
        $taxTotal = 0.0;

        foreach ($cart->items as $item) {
            $lineSubtotal = (float) $item->quantity * (float) $item->unit_price;
            $lineMrp = (float) $item->quantity * (float) $item->mrp;
            $taxRate = (float) ($item->gst_rate_snapshot ?? 0);

            $subtotal += $lineSubtotal;
            $totalMrp += $lineMrp;
            $taxTotal += $lineSubtotal * $taxRate / (100 + $taxRate);
        }

        $deliveryRule ??= $this->deliveryChargeService->resolve($cart->customer, $subtotal);
        $originalDeliveryCharge = round((float) $deliveryRule['delivery_charge'], 2);
        $merchandiseDiscount = is_array($couponEffect)
            ? (float) ($couponEffect['merchandise_discount'] ?? $couponEffect['discount'] ?? 0)
            : (float) $couponEffect;
        $deliveryDiscount = is_array($couponEffect) ? (float) ($couponEffect['delivery_discount'] ?? 0) : 0.0;
        $merchandiseDiscount = round(min(max(0, $merchandiseDiscount), $subtotal), 2);
        $deliveryDiscount = round(min(max(0, $deliveryDiscount), $originalDeliveryCharge), 2);
        $finalDeliveryCharge = round(max(0, $originalDeliveryCharge - $deliveryDiscount), 2);
        $amountBeforeCredit = round(max(0, $subtotal - $merchandiseDiscount) + $finalDeliveryCharge, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'total_mrp' => round($totalMrp, 2),
            'total_savings' => round(max(0, $totalMrp - $subtotal), 2),
            'tax_total' => round($taxTotal, 2),
            'original_delivery_charge' => $originalDeliveryCharge,
            'delivery_discount_total' => $deliveryDiscount,
            'delivery_charge' => $finalDeliveryCharge,
            'discount_total' => $merchandiseDiscount,
            'coupon_discount_amount' => round($merchandiseDiscount + $deliveryDiscount, 2),
            'amount_before_customer_credit' => $amountBeforeCredit,
            'customer_credit_used' => 0.0,
            'grand_total' => $amountBeforeCredit,
        ];
    }

    private function cartSubtotal(Cart $cart): float
    {
        $cart->loadMissing('items');

        return round((float) $cart->items->sum(fn (CartItem $item) => (float) $item->quantity * (float) $item->unit_price), 2);
    }

    public function createOrder(Cart $cart, string $sessionId, array $checkoutData, array $totals, string $initialStatus = 'placed'): Order
    {
        /** @var Order $order */
        $order = $this->orderRepository->create(array_merge($checkoutData, $totals, [
            'order_number' => $this->generateOrderNumber(),
            'cart_id' => $cart->id,
            'stock_location_id' => $cart->stock_location_id,
            'store_name_snapshot' => $cart->stockLocation?->name,
            'store_code_snapshot' => $cart->stockLocation?->code,
            'session_id' => $sessionId,
            'coupon_id' => $cart->coupon_id,
            'coupon_code_snapshot' => $cart->coupon_code,
            'coupon_purpose_snapshot' => $cart->coupon?->purpose,
            'coupon_discount_amount' => $totals['coupon_discount_amount'] ?? $totals['discount_total'],
            'payment_method' => $checkoutData['payment_method'] ?? 'cod',
            'payment_status' => (float) ($totals['grand_total'] ?? 0) <= 0 ? 'paid' : 'pending',
            'order_status' => $initialStatus,
            'placed_at' => $initialStatus === 'placed' ? now() : null,
        ]));

        return $order;
    }

    private function applyCustomerCredit(array $totals, ?Customer $customer, array $checkoutData): array
    {
        $enabled = filter_var($this->settingService->get('checkout.customer_credit_redemption_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $requested = (float) ($checkoutData['customer_credit_amount'] ?? 0);
        $useCredit = filter_var($checkoutData['use_customer_credit'] ?? false, FILTER_VALIDATE_BOOLEAN) || $requested > 0;

        if (! $enabled || ! $customer || ! $useCredit) {
            return $totals;
        }

        if ($requested < 0) {
            throw new InvalidArgumentException('Customer Credit amount cannot be negative.');
        }

        $payable = round((float) $totals['amount_before_customer_credit'], 2);
        $maximum = $this->customerCreditService->maximumUsable($customer, $payable);
        $creditUsed = $requested > 0 ? round($requested, 2) : $maximum;

        if ($creditUsed > $maximum) {
            throw new InvalidArgumentException('Customer Credit amount exceeds the usable balance for this order.');
        }

        $totals['customer_credit_used'] = $creditUsed;
        $totals['grand_total'] = round(max(0, $payable - $creditUsed), 2);

        return $totals;
    }

    public function createCouponUsageIfApplied(Order $order, ?Coupon $coupon, float $discountAmount): void
    {
        if (! $coupon || $discountAmount <= 0) {
            return;
        }

        $this->couponService->createUsageForOrder($order, $coupon, $discountAmount);
    }

    public function createOrderItems(Order $order, Cart $cart): void
    {
        foreach ($cart->items as $item) {
            $productId = $item->productVariant?->product_id;
            $brand = $item->productVariant?->product?->brand;
            $lineSubtotal = round((float) $item->quantity * (float) $item->unit_price, 2);
            $lineMrp = round((float) $item->quantity * (float) $item->mrp, 2);
            $taxRate = (float) ($item->gst_rate_snapshot ?? 0);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_variant_id' => $item->product_variant_id,
                'product_id' => $productId,
                'brand_id_snapshot' => $brand?->id,
                'brand_name_snapshot' => $brand?->name,
                'sale_type' => $item->sale_type,
                'daily_offer_id' => $item->daily_offer_id,
                'product_name_snapshot' => $item->product_name_snapshot,
                'variant_name_snapshot' => $item->variant_name_snapshot,
                'sku_snapshot' => $item->sku_snapshot,
                'barcode_snapshot' => $item->productVariant?->barcode,
                'hsn_code_snapshot' => $item->hsn_code_snapshot,
                'gst_rate_snapshot' => $item->gst_rate_snapshot,
                'attributes_snapshot' => $item->attributes_snapshot,
                'quantity' => $item->quantity,
                'mrp' => $item->mrp,
                'unit_price' => $item->unit_price,
                'line_subtotal' => $lineSubtotal,
                'line_mrp_total' => $lineMrp,
                'line_savings' => round(max(0, $lineMrp - $lineSubtotal), 2),
                'tax_amount' => round($lineSubtotal * $taxRate / (100 + $taxRate), 2),
                'line_total' => $lineSubtotal,
            ]);
        }
    }

    public function deductInventoryForOrder(Order $order, ?iterable $lockedInventories = null): void
    {
        $lockedInventoryCollection = $lockedInventories ? collect($lockedInventories) : null;

        foreach ($order->items as $item) {
            $remaining = (float) $item->quantity;
            $inventories = $lockedInventoryCollection
                ? $lockedInventoryCollection->where('product_variant_id', $item->product_variant_id)->sortBy('stock_location_id')->values()
                : Inventory::query()
                    ->active()
                    ->where('product_variant_id', $item->product_variant_id)
                    ->when($order->stock_location_id !== null, fn ($query) => $query->where('stock_location_id', $order->stock_location_id))
                    ->orderBy('stock_location_id')
                    ->lockForUpdate()
                    ->get();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $deductQuantity = min($remaining, $inventory->available_quantity);

                if ($deductQuantity > 0) {
                    $updatedInventory = $this->inventoryService->adjustStock($inventory, 'sale', $deductQuantity, 'Order '.$order->order_number);
                    $inventory->forceFill([
                        'quantity_on_hand' => $updatedInventory->quantity_on_hand,
                        'reserved_quantity' => $updatedInventory->reserved_quantity,
                        'damaged_quantity' => $updatedInventory->damaged_quantity,
                    ]);
                    $remaining -= $deductQuantity;
                }
            }

            if ($remaining > 0) {
                throw new InvalidArgumentException('Unable to deduct inventory for '.$item->product_name_snapshot.'.');
            }
        }
    }

    private function pendingReservedQuantityForVariant(PendingOrder $pending, int $productVariantId): float
    {
        return (float) InventoryMovement::query()
            ->where('reference_type', PendingOrder::class)
            ->where('reference_id', $pending->id)
            ->where('product_variant_id', $productVariantId)
            ->whereIn('movement_type', ['reservation', 'reservation_release'])
            ->selectRaw("SUM(CASE WHEN movement_type = 'reservation' THEN quantity ELSE -quantity END) as reserved_quantity")
            ->value('reserved_quantity');
    }

    private function validateOrderItemsStillPurchasable(Order $order): void
    {
        foreach ($order->items as $item) {
            $variant = $item->productVariant?->load('product');

            if (! $variant || ! $variant->status || ! $variant->product?->status) {
                throw new InvalidArgumentException('One or more order items are no longer available.');
            }
        }
    }

    private function finalizePaidRazorpayOrder(Order $lockedOrder, string $note): void
    {
        if ($lockedOrder->order_status === 'placed') {
            return;
        }

        $this->validateOrderItemsStillPurchasable($lockedOrder);

        $pending = $lockedOrder->cart_id
            ? PendingOrder::query()->active()->where('cart_id', $lockedOrder->cart_id)->lockForUpdate()->first()
            : null;

        if ($pending) {
            $this->pendingOrderService->releaseReservedStock($pending, 'Order '.$lockedOrder->order_number.' paid');
        }

        if ((float) $lockedOrder->customer_credit_used > 0 && $lockedOrder->customer) {
            $this->customerCreditService->debitForOrder($lockedOrder->customer, $lockedOrder, (float) $lockedOrder->customer_credit_used);
        }

        $this->deductInventoryForOrder($lockedOrder);

        if ($lockedOrder->coupon_id && (float) $lockedOrder->coupon_discount_amount > 0) {
            $coupon = Coupon::query()->find($lockedOrder->coupon_id);
            $this->createCouponUsageIfApplied($lockedOrder, $coupon, (float) $lockedOrder->coupon_discount_amount);
        }

        $oldStatus = $lockedOrder->order_status;
        $lockedOrder->update([
            'order_status' => 'placed',
            'placed_at' => $lockedOrder->placed_at ?? now(),
        ]);
        $this->createStatusHistory($lockedOrder, $oldStatus, 'placed', $note);
        $this->cartService->clearCart($lockedOrder->session_id);
        $lockedOrder->refresh();
        $this->notificationService->notifyAdminNewOrder($lockedOrder);
        $this->notificationService->notifyRazorpayPaymentSuccess($lockedOrder, $lockedOrder->payment);
    }

    public function restoreStockOnCancellation(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = Inventory::query()
                ->where('product_variant_id', $item->product_variant_id)
                ->when($order->stock_location_id !== null, fn ($query) => $query->where('stock_location_id', $order->stock_location_id))
                ->orderByDesc('stock_location_id')
                ->first();

            if (! $inventory) {
                throw new InvalidArgumentException('Unable to restore stock for '.$item->product_name_snapshot.'.');
            }

            $this->inventoryService->adjustStock($inventory, 'cancellation_return', (float) $item->quantity, 'Cancelled order '.$order->order_number);
        }
    }

    public function createStatusHistory(Order $order, ?string $oldStatus, string $newStatus, ?string $note = null): void
    {
        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'changed_by' => Auth::id(),
        ]);
    }

    public function generateOrderNumber(): string
    {
        do {
            $number = 'GK'.now()->format('ymd').Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }

    private function lockedCartForSession(string $sessionId): Cart
    {
        $cart = $this->cartService->cartForSession($sessionId);

        /** @var Cart $lockedCart */
        $lockedCart = Cart::query()
            ->with(['items.productVariant.product.brand', 'items.dailyOffer', 'coupon', 'stockLocation'])
            ->whereKey($cart->id)
            ->lockForUpdate()
            ->firstOrFail();

        return $lockedCart;
    }

    private function lockedActivePendingForCart(Cart $cart): ?PendingOrder
    {
        if (! $cart->customer_id) {
            return null;
        }

        $pending = PendingOrder::query()
            ->where('cart_id', $cart->id)
            ->whereIn('status', [PendingOrder::STATUS_ACTIVE, PendingOrder::STATUS_CONVERTED])
            ->latest()
            ->lockForUpdate()
            ->first();

        if (! $pending) {
            $pending = $this->pendingOrderService->ensureActiveForCart($cart);
            $pending = $pending
                ? PendingOrder::query()->whereKey($pending->id)->lockForUpdate()->first()
                : null;
        }

        if (! $pending) {
            return null;
        }

        if ($pending->status === PendingOrder::STATUS_CONVERTED) {
            throw new InvalidArgumentException('This cart has already been placed as an order.');
        }

        if ($pending->expires_at->isPast()) {
            $this->pendingOrderService->close($pending, PendingOrder::CLOSE_EXPIRED);
            throw new InvalidArgumentException('Your cart expired because it was not ordered within the allowed time.');
        }

        return $pending;
    }

    private function lockInventoryRowsForCart(Cart $cart)
    {
        $variantIds = $cart->items
            ->pluck('product_variant_id')
            ->unique()
            ->sort()
            ->values();

        return Inventory::query()
            ->active()
            ->whereIn('product_variant_id', $variantIds)
            ->when($cart->stock_location_id !== null, fn ($query) => $query->where('stock_location_id', $cart->stock_location_id))
            ->orderBy('product_variant_id')
            ->orderBy('stock_location_id')
            ->lockForUpdate()
            ->get();
    }
}
