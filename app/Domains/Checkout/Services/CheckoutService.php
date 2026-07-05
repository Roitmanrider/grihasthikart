<?php

namespace App\Domains\Checkout\Services;

use App\Domains\Cart\Services\CartService;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function checkoutData(string $sessionId): array
    {
        return $this->cartService->getCartSummary($sessionId);
    }
}
