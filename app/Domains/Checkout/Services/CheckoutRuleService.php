<?php

namespace App\Domains\Checkout\Services;

use App\Domains\Delivery\Services\DeliverySlotService;
use App\Domains\Setting\Services\BusinessSettingService;
use Carbon\Carbon;
use InvalidArgumentException;

class CheckoutRuleService
{
    public function __construct(
        private readonly BusinessSettingService $settings,
        private readonly DeliverySlotService $deliverySlotService
    ) {}

    public function validateCheckout(array $data, float $subtotal): void
    {
        $checkout = $this->settings->checkoutSettings();

        if (! $checkout['cod_enabled']) {
            throw new InvalidArgumentException('Cash on Delivery is currently unavailable.');
        }

        if ($subtotal < $checkout['minimum_order_amount']) {
            throw new InvalidArgumentException('Minimum order amount is Rs. '.number_format($checkout['minimum_order_amount'], 2).'.');
        }

        $this->validateDeliveryDate($data['delivery_date'] ?? null);
        $this->validateDeliverySlot($data['delivery_slot'] ?? null);
    }

    public function validateDeliveryDate(?string $deliveryDate): void
    {
        if (! $deliveryDate) {
            return;
        }

        $settings = $this->settings->checkoutSettings();
        $date = Carbon::parse($deliveryDate)->startOfDay();
        $today = now()->startOfDay();

        if (! $settings['custom_delivery_date_enabled']) {
            throw new InvalidArgumentException('Custom delivery date selection is currently disabled.');
        }

        if ($date->lt($today)) {
            throw new InvalidArgumentException('Delivery date cannot be in the past.');
        }

        if ($date->isSameDay($today)) {
            if (! $settings['today_delivery_enabled']) {
                throw new InvalidArgumentException('Today delivery is currently disabled.');
            }

            if (now()->format('H:i') >= $settings['today_delivery_cutoff_time']) {
                throw new InvalidArgumentException('Today delivery cutoff time has passed.');
            }
        }

        if ($date->gt($today->copy()->addDays($settings['max_delivery_days_ahead']))) {
            throw new InvalidArgumentException('Delivery date is beyond the allowed delivery window.');
        }
    }

    public function validateDeliverySlot(?string $deliverySlot): void
    {
        if (! $deliverySlot) {
            return;
        }

        $exists = $this->deliverySlotService->activeSlots()
            ->contains(fn ($slot) => $slot->label === $deliverySlot);

        if (! $exists) {
            throw new InvalidArgumentException('Selected delivery slot is not available.');
        }
    }
}
