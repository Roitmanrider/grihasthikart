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

    public function checkoutData(string $sessionId, ?Store $session = null, ?string $deliveryDate = null): array
    {
        $data = $this->cartService->getCartSummary($sessionId);
        $customer = $session ? $this->customerAuthService->currentCustomer($session) : null;
        $selectedDeliveryDate = $this->deliverySlotService->defaultDeliveryDate($deliveryDate);

        $data['customer'] = $customer;
        $approvedAddresses = $customer
            ? $customer->approvedAddresses()->orderByDesc('is_default')->get()
            : collect();

        $data['approvedAddresses'] = $approvedAddresses;
        $data['preferredAddress'] = $approvedAddresses->firstWhere('is_default', true) ?? $approvedAddresses->first();
        $data['selectedDeliveryDate'] = $selectedDeliveryDate;
        $data['minimumDeliveryDate'] = $this->deliverySlotService->earliestSelectableDeliveryDate();
        $data['deliverySlots'] = $this->deliverySlotService->activeSlotsForDate($selectedDeliveryDate);
        $data['checkoutSettings'] = $this->settingService->checkoutSettings();
        $data['paymentSettings'] = $this->settingService->publicPaymentSettings();
        $data['enabledPaymentMethods'] = $this->paymentService->enabledMethods();

        return $data;
    }
}
