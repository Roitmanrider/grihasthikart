<?php

namespace App\Domains\Setting\Services;

use App\Domains\Setting\Contracts\BusinessSettingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class BusinessSettingService
{
    public function __construct(
        private readonly BusinessSettingRepositoryInterface $repository
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('business_setting_'.$key, function () use ($key, $default) {
            $setting = $this->repository->findByKey($key);

            return $setting ? $this->cast($setting->value, $setting->value_type) : $default;
        });
    }

    public function set(string $key, mixed $value)
    {
        $setting = $this->repository->updateByKey($key, $this->serialize($value));
        Cache::forget('business_setting_'.$key);

        return $setting;
    }

    public function getGroup(string $group): array
    {
        return $this->repository->group($group)
            ->mapWithKeys(fn ($setting) => [$setting->group.'.'.$setting->key => $this->cast($setting->value, $setting->value_type)])
            ->all();
    }

    public function checkoutSettings(): array
    {
        return [
            'minimum_order_amount' => (float) $this->get('checkout.minimum_order_amount', 0),
            'delivery_charge' => (float) $this->get('checkout.delivery_charge', 0),
            'cod_enabled' => (bool) $this->get('checkout.cod_enabled', true),
            'today_delivery_enabled' => (bool) $this->get('checkout.today_delivery_enabled', true),
            'today_delivery_cutoff_time' => $this->get('checkout.today_delivery_cutoff_time', '14:00'),
            'custom_delivery_date_enabled' => (bool) $this->get('checkout.custom_delivery_date_enabled', true),
            'max_delivery_days_ahead' => (int) $this->get('checkout.max_delivery_days_ahead', 7),
            'default_state' => $this->get('checkout.default_state'),
            'default_city' => $this->get('checkout.default_city'),
            'store_contact_mobile' => $this->get('checkout.store_contact_mobile'),
            'store_whatsapp_number' => $this->get('checkout.store_whatsapp_number'),
        ];
    }

    public function updateCheckoutSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set('checkout.'.$key, $value);
        }
    }

    private function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            default => $value,
        };
    }

    private function serialize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
