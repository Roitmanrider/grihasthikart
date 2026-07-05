<?php

namespace App\Http\Controllers\Frontend;

use App\Domains\Cart\Services\CartService;
use App\Domains\Coupon\Services\CouponService;
use App\Domains\Customer\Services\CustomerAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApplyCouponRequest;
use InvalidArgumentException;

class CouponController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CouponService $couponService,
        private readonly CustomerAuthService $customerAuthService
    ) {}

    public function apply(ApplyCouponRequest $request)
    {
        $sessionId = $this->cartService->sessionIdentifier($request->session());
        $cart = $this->cartService->cartForSession($sessionId);

        try {
            $this->couponService->applyCouponToCart(
                $cart,
                $request->validated('code'),
                $this->customerAuthService->currentCustomer($request->session())
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['coupon' => $exception->getMessage()]);
        }

        return back()->with('success', 'Coupon applied successfully.');
    }

    public function remove()
    {
        $cart = $this->cartService->cartForSession($this->cartService->sessionIdentifier(request()->session()));
        $this->couponService->removeCouponFromCart($cart);

        return back()->with('success', 'Coupon removed successfully.');
    }
}
