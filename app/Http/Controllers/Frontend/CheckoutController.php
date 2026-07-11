<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cart\Services\CartService;
use App\Domains\Checkout\Services\CheckoutService;
use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateRazorpayOrderRequest;
use App\Http\Requests\FailRazorpayPaymentRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\VerifyRazorpayPaymentRequest;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerAuthService $customerAuthService
    ) {}

    public function show()
    {
        $sessionId = $this->cartService->sessionIdentifier(request()->session());
        $summary = $this->checkoutService->checkoutData(
            $sessionId,
            request()->session(),
            request('delivery_date')
        );

        if ($summary['cart']->items->isEmpty()) {
            return redirect()
                ->route('cart.show')
                ->withErrors(['checkout' => 'Your cart is empty.']);
        }

        return view('frontend.checkout.show', $summary);
    }

    public function place(PlaceOrderRequest $request)
    {
        try {
            $data = $request->validated();
            $customer = $this->customerAuthService->currentCustomer($request->session());

            if ($customer) {
                $data['customer_id'] = $customer->id;
            }

            if (($data['payment_method'] ?? null) === 'razorpay') {
                return back()
                    ->withInput()
                    ->withErrors(['checkout' => 'Please use the online payment checkout to complete Razorpay payment.']);
            }

            $order = $this->orderService->placeOrderFromCart(
                $this->cartService->sessionIdentifier($request->session()),
                $data
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['checkout' => $exception->getMessage()]);
        }

        return redirect()->route('checkout.success', $order->order_number);
    }

    public function success(string $orderNumber)
    {
        $order = $this->orderRepository->findByOrderNumberForSession(
            $orderNumber,
            $this->cartService->sessionIdentifier(request()->session())
        );

        return view('frontend.checkout.success', compact('order'));
    }

    public function createRazorpayOrder(CreateRazorpayOrderRequest $request)
    {
        try {
            $data = $request->validated();
            $customer = $this->customerAuthService->currentCustomer($request->session());

            if ($customer) {
                $data['customer_id'] = $customer->id;
            }

            $checkout = $this->orderService->createRazorpayOrderFromCart(
                $this->cartService->sessionIdentifier($request->session()),
                $data
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'key' => $checkout['razorpay']['key_id'],
            'amount' => $checkout['razorpay']['amount'],
            'currency' => $checkout['razorpay']['currency'],
            'name' => 'GrihasthiKart',
            'description' => 'Order '.$checkout['order']->order_number,
            'order_id' => $checkout['razorpay']['order_id'],
            'order_number' => $checkout['order']->order_number,
            'prefill' => [
                'name' => $checkout['order']->customer_name,
                'email' => $checkout['order']->customer_email,
                'contact' => $checkout['order']->customer_mobile,
            ],
        ]);
    }

    public function verifyRazorpayPayment(VerifyRazorpayPaymentRequest $request)
    {
        $data = $request->validated();

        try {
            $order = $this->orderRepository->findByOrderNumberForSession(
                $data['order_number'],
                $this->cartService->sessionIdentifier($request->session())
            );
            $this->orderService->completeRazorpayPayment($order, $data);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'redirect_url' => route('checkout.success', $data['order_number']),
        ]);
    }

    public function failRazorpayPayment(FailRazorpayPaymentRequest $request)
    {
        $data = $request->validated();

        try {
            $order = $this->orderRepository->findByOrderNumberForSession(
                $data['order_number'],
                $this->cartService->sessionIdentifier($request->session())
            );

            if (($data['razorpay_order_id'] ?? null) && $order->payment?->gateway_order_id !== $data['razorpay_order_id']) {
                throw new InvalidArgumentException('Payment order reference does not match.');
            }

            $this->orderService->failRazorpayPayment($order, $data['reason'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Online payment was not completed. Your cart is still available.']);
    }
}
