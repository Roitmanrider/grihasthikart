<?php

namespace App\Domains\Delivery\Services;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\Customer;

class DeliveryChargeService
{
    public const SOURCE_CUSTOMER_OVERRIDE = 'CUSTOMER_OVERRIDE';

    public const SOURCE_PREMIUM = 'PREMIUM';

    public const SOURCE_STANDARD = 'STANDARD';

    public const SOURCE_RUNTIME_FALLBACK = 'RUNTIME_FALLBACK';

    public function __construct(private readonly BusinessSettingService $settings) {}

    public function resolve(?Customer $customer = null, float|string $merchandiseTotal = 0): array
    {
        $tier = $customer?->is_premium ? 'PREMIUM' : 'STANDARD';
        $minimum = $this->resolveField($customer, 'minimum_order_amount', $tier);
        $charge = $this->resolveField($customer, 'delivery_charge', $tier);
        $threshold = $this->resolveField($customer, 'free_delivery_threshold', $tier);
        $eligibleBase = $this->money($merchandiseTotal);
        $deliveryCharge = $this->chargeFor($charge['value'], $threshold['value'], $eligibleBase);

        return [
            'customer_tier' => $tier,
            'minimum_order_amount' => $minimum['value'],
            'delivery_charge_configured' => $charge['value'],
            'free_delivery_threshold' => $threshold['value'],
            'delivery_charge' => $deliveryCharge,
            'is_free_delivery' => $deliveryCharge === 0.0,
            'eligible_merchandise_total' => $eligibleBase,
            'free_delivery_remaining' => $this->freeDeliveryRemaining($threshold['value'], $eligibleBase, $deliveryCharge),
            'sources' => [
                'minimum_order_amount' => $minimum['source'],
                'delivery_charge' => $charge['source'],
                'free_delivery_threshold' => $threshold['source'],
            ],
        ];
    }

    private function resolveField(?Customer $customer, string $field, string $tier): array
    {
        if ($customer?->custom_delivery_rules_enabled) {
            $overrideField = $field.'_override';

            if ($customer->{$overrideField} !== null) {
                return [
                    'value' => $this->money($customer->{$overrideField}),
                    'source' => self::SOURCE_CUSTOMER_OVERRIDE,
                ];
            }
        }

        if ($tier === 'PREMIUM') {
            $premiumValue = $this->settingValue('checkout.premium_'.$field);

            if ($premiumValue !== null) {
                return [
                    'value' => $this->money($premiumValue),
                    'source' => self::SOURCE_PREMIUM,
                ];
            }
        }

        $standardValue = $this->standardValue($field);

        return [
            'value' => $this->money($standardValue['value']),
            'source' => $standardValue['source'],
        ];
    }

    private function standardValue(string $field): array
    {
        $key = match ($field) {
            'minimum_order_amount' => 'checkout.minimum_order_amount',
            'delivery_charge' => 'checkout.delivery_charge',
            default => 'checkout.'.$field,
        };

        $value = $this->settingValue($key);

        if ($value !== null) {
            return ['value' => $value, 'source' => self::SOURCE_STANDARD];
        }

        return [
            'value' => $field === 'free_delivery_threshold' ? null : 0,
            'source' => self::SOURCE_RUNTIME_FALLBACK,
        ];
    }

    private function settingValue(string $key): mixed
    {
        return $this->settings->get($key, null);
    }

    private function chargeFor(float $configuredCharge, ?float $threshold, float $eligibleBase): float
    {
        if ($configuredCharge <= 0) {
            return 0.0;
        }

        if ($threshold === null) {
            return $configuredCharge;
        }

        if ($threshold <= 0) {
            return 0.0;
        }

        if ($eligibleBase >= $threshold) {
            return 0.0;
        }

        return $configuredCharge;
    }

    private function freeDeliveryRemaining(?float $threshold, float $eligibleBase, float $deliveryCharge): float
    {
        if ($deliveryCharge <= 0 || $threshold === null || $threshold <= 0 || $eligibleBase >= $threshold) {
            return 0.0;
        }

        return $this->money($threshold - $eligibleBase);
    }

    private function money(float|string|null $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round(max(0, (float) ($value ?? 0)), 2);
    }
}
