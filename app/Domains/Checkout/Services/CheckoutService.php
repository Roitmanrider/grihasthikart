<?php

namespace App\Domains\Checkout\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Delivery\Services\DeliverySlotService;
use App\Domains\Payment\Services\PaymentService;
use App\Domains\Setting\Services\BusinessSettingService;
use Illuminate\Session\Store;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CustomerAuthService $customerAuthService,
        private readonly DeliverySlotService $deliverySlotService,
        private readonly BusinessSettingService $settingService,
        private readonly PaymentService $paymentService
    ) {}

    public function checkoutData(string $sessionId, ?Store $session = null): array
    {
        $data = $this->cartService->getCartSummary($sessionId);
        $customer = $session ? $this->customerAuthService->currentCustomer($session) : null;

        $data['customer'] = $customer;
        $data['approvedAddresses'] = $customer
            ? $customer->approvedAddresses()->orderByDesc('is_default')->get()
            : collect();
        $data['deliverySlots'] = $this->deliverySlotService->activeSlots();
        $data['checkoutSettings'] = $this->settingService->checkoutSettings();
        $data['paymentSettings'] = $this->settingService->publicPaymentSettings();
        $data['enabledPaymentMethods'] = $this->paymentService->enabledMethods();

        return $data;
    }
}
