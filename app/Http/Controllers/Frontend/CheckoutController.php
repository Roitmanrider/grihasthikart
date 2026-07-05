<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cart\Services\CartService;
use App\Domains\Checkout\Services\CheckoutService;
use App\Domains\Order\Contracts\OrderRepositoryInterface;
use App\Domains\Order\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly CartService $cartService,
        private readonly OrderService $orderService,
        private readonly OrderRepositoryInterface $orderRepository
    ) {}

    public function show()
    {
        $sessionId = $this->cartService->sessionIdentifier(request()->session());
        $summary = $this->checkoutService->checkoutData($sessionId);

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
            $order = $this->orderService->placeOrderFromCart(
                $this->cartService->sessionIdentifier($request->session()),
                $request->validated()
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
}
