<?php

namespace App\Domains\Order\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Checkout\Services\CheckoutRuleService;
use App\Domains\Coupon\Services\CouponService;
use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class OrderService
{
    private const ALLOWED_TRANSITIONS = [
        'placed' => ['confirmed', 'cancelled'],
        'confirmed' => ['preparing', 'cancelled'],
        'preparing' => ['ready_for_delivery', 'cancelled'],
        'ready_for_delivery' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CartService $cartService,
        private readonly InventoryService $inventoryService,
        private readonly CheckoutRuleService $checkoutRuleService,
        private readonly CouponService $couponService,
        private readonly PaymentService $paymentService,
        private readonly BusinessSettingService $settingService
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->orderRepository->paginatedList($filters, $perPage);
    }

    public function placeOrderFromCart(string $sessionId, array $checkoutData): Order
    {
        return DB::transaction(function () use ($sessionId, $checkoutData) {
            $cart = $this->cartService->cartForSession($sessionId);

            $this->validateCartIsNotEmpty($cart);
            $this->validateCartItemsStillPurchasable($cart);
            $this->validateInventoryAvailabilityForEveryCartItem($cart);

            $customer = isset($checkoutData['customer_id']) ? Customer::query()->find($checkoutData['customer_id']) : null;
            $couponData = $this->couponService->revalidateAppliedCoupon($cart, $customer);
            $totals = $this->calculateTotalsFromCartSnapshots($cart, $couponData['discount']);
            $this->checkoutRuleService->validateCheckout($checkoutData, $totals['subtotal']);
            $order = $this->createOrder($cart, $sessionId, $checkoutData, $totals);
            $this->createOrderItems($order, $cart);
            $this->createCouponUsageIfApplied($order, $couponData['coupon'], $couponData['discount']);
            $this->paymentService->createForOrder($order, $checkoutData['payment_method'] ?? 'cod');
            $this->deductInventoryForOrder($order);
            $this->createStatusHistory($order, null, 'placed', 'Order placed.');
            $this->couponService->clearCouponAfterOrder($cart);
            $this->cartService->clearCart($sessionId);

            return $order->fresh(['items', 'statusHistories', 'payment']);
        });
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

            if (! in_array($newStatus, self::ALLOWED_TRANSITIONS[$oldStatus] ?? [], true)) {
                throw new InvalidArgumentException('This order status transition is not allowed.');
            }

            if ($newStatus === 'cancelled') {
                $this->restoreStockOnCancellation($lockedOrder);
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

            return $lockedOrder->fresh(['items', 'statusHistories']);
        });
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

    public function validateInventoryAvailabilityForEveryCartItem(Cart $cart): void
    {
        foreach ($cart->items as $item) {
            if ($this->inventoryService->getAvailableQuantity($item->product_variant_id) < (float) $item->quantity) {
                throw new InvalidArgumentException('Insufficient stock for '.$item->product_name_snapshot.' / '.$item->variant_name_snapshot.'.');
            }
        }
    }

    public function calculateTotalsFromCartSnapshots(Cart $cart, float $couponDiscount = 0): array
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

        $deliveryCharge = (float) $this->settingService->get('checkout.delivery_charge', 0);
        $couponDiscount = round(min(max(0, $couponDiscount), $subtotal), 2);

        return [
            'subtotal' => round($subtotal, 2),
            'total_mrp' => round($totalMrp, 2),
            'total_savings' => round(max(0, $totalMrp - $subtotal), 2),
            'tax_total' => round($taxTotal, 2),
            'delivery_charge' => $deliveryCharge,
            'discount_total' => $couponDiscount,
            'grand_total' => round(max(0, $subtotal - $couponDiscount) + $deliveryCharge, 2),
        ];
    }

    public function createOrder(Cart $cart, string $sessionId, array $checkoutData, array $totals): Order
    {
        /** @var Order $order */
        $order = $this->orderRepository->create(array_merge($checkoutData, $totals, [
            'order_number' => $this->generateOrderNumber(),
            'cart_id' => $cart->id,
            'session_id' => $sessionId,
            'coupon_id' => $cart->coupon_id,
            'coupon_code_snapshot' => $cart->coupon_code,
            'coupon_discount_amount' => $totals['discount_total'],
            'payment_method' => $checkoutData['payment_method'] ?? 'cod',
            'payment_status' => 'pending',
            'order_status' => 'placed',
            'placed_at' => now(),
        ]));

        return $order;
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
            $lineSubtotal = round((float) $item->quantity * (float) $item->unit_price, 2);
            $lineMrp = round((float) $item->quantity * (float) $item->mrp, 2);
            $taxRate = (float) ($item->gst_rate_snapshot ?? 0);

            OrderItem::query()->create([
                'order_id' => $order->id,
                'product_variant_id' => $item->product_variant_id,
                'product_id' => $productId,
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

    public function deductInventoryForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            $remaining = (float) $item->quantity;
            $inventories = Inventory::query()
                ->active()
                ->where('product_variant_id', $item->product_variant_id)
                ->orderBy('stock_location_id')
                ->get();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $deductQuantity = min($remaining, $inventory->available_quantity);

                if ($deductQuantity > 0) {
                    $this->inventoryService->adjustStock($inventory, 'sale', $deductQuantity, 'Order '.$order->order_number);
                    $remaining -= $deductQuantity;
                }
            }

            if ($remaining > 0) {
                throw new InvalidArgumentException('Unable to deduct inventory for '.$item->product_name_snapshot.'.');
            }
        }
    }

    public function restoreStockOnCancellation(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = Inventory::query()
                ->where('product_variant_id', $item->product_variant_id)
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
}
