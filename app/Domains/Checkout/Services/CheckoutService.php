<?php

namespace App\Domains\Checkout\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Customer\Services\CustomerAuthService;
use Illuminate\Session\Store;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CustomerAuthService $customerAuthService
    ) {}

    public function checkoutData(string $sessionId, ?Store $session = null): array
    {
        $data = $this->cartService->getCartSummary($sessionId);
        $customer = $session ? $this->customerAuthService->currentCustomer($session) : null;

        $data['customer'] = $customer;
        $data['approvedAddresses'] = $customer
            ? $customer->approvedAddresses()->orderByDesc('is_default')->get()
            : collect();

        return $data;
    }
}
