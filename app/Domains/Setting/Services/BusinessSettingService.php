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
            'cod_enabled' => (bool) $this->get('payment.cod_enabled', $this->get('checkout.cod_enabled', true)),
            'today_delivery_enabled' => (bool) $this->get('checkout.today_delivery_enabled', true),
            'today_delivery_cutoff_time' => $this->get('checkout.today_delivery_cutoff_time', '14:00'),
            'custom_delivery_date_enabled' => (bool) $this->get('checkout.custom_delivery_date_enabled', true),
            'max_delivery_days_ahead' => (int) $this->get('checkout.max_delivery_days_ahead', 7),
            'default_state' => $this->get('checkout.default_state'),
            'default_city' => $this->get('checkout.default_city'),
            'store_contact_mobile' => $this->get('checkout.store_contact_mobile'),
            'store_whatsapp_number' => $this->get('checkout.store_whatsapp_number'),
            'customer_invoice_enabled' => (bool) $this->get('order.customer_invoice_enabled', true),
        ];
    }

    public function customerInvoiceEnabled(): bool
    {
        return (bool) $this->get('order.customer_invoice_enabled', true);
    }

    public function paymentSettings(): array
    {
        return [
            'cod_enabled' => (bool) $this->get('payment.cod_enabled', $this->get('checkout.cod_enabled', true)),
            'qr_enabled' => (bool) $this->get('payment.qr_enabled', false),
            'razorpay_enabled' => (bool) $this->get('payment.razorpay_enabled', false),
            'qr_label' => $this->get('payment.qr_label', 'Pay by QR'),
            'qr_upi_id' => $this->get('payment.qr_upi_id'),
            'qr_display_name' => $this->get('payment.qr_display_name'),
            'qr_image_path' => $this->get('payment.qr_image_path'),
            'razorpay_key_id' => $this->get('payment.razorpay_key_id'),
            'razorpay_key_secret' => $this->get('payment.razorpay_key_secret'),
            'currency' => $this->get('payment.currency', 'INR'),
        ];
    }

    public function publicPaymentSettings(): array
    {
        $settings = $this->paymentSettings();
        unset($settings['razorpay_key_secret']);

        return $settings;
    }

    public function razorpayConfigured(): bool
    {
        return (bool) ($this->get('payment.razorpay_key_id') && $this->get('payment.razorpay_key_secret'));
    }

    public function taxSettings(): array
    {
        return [
            'prices_include_gst' => (bool) $this->get('tax.prices_include_gst', true),
            'default_gst_rate' => (float) $this->get('tax.default_gst_rate', 0),
            'company_gstin' => $this->get('tax.company_gstin'),
            'company_legal_name' => $this->get('tax.company_legal_name'),
            'company_address' => $this->get('tax.company_address'),
        ];
    }

    public function businessSettings(): array
    {
        return [
            'name' => $this->get('business.name', 'GrihasthiKart'),
            'support_email' => $this->get('business.support_email'),
            'support_phone' => $this->get('business.support_phone'),
            'whatsapp_number' => $this->get('business.whatsapp_number'),
            'address' => $this->get('business.address'),
            'city' => $this->get('business.city'),
            'state' => $this->get('business.state'),
            'pincode' => $this->get('business.pincode'),
            'instagram_url' => $this->get('business.instagram_url'),
            'business_hours' => $this->get('business.business_hours'),
            'google_maps_url' => $this->get('business.google_maps_url'),
        ];
    }

    public function updateCheckoutSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'customer_invoice_enabled') {
                $this->set('order.customer_invoice_enabled', $value);

                continue;
            }

            $this->set('checkout.'.$key, $value);
        }
    }

    public function updatePaymentSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($key === 'razorpay_key_secret' && ($value === null || $value === '')) {
                continue;
            }

            $this->set('payment.'.$key, $value);
        }
    }

    public function updateBusinessSettings(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set('business.'.$key, $value);
        }
    }

    public function whatsappUrl(): ?string
    {
        $number = $this->digitsOnly((string) $this->get('business.whatsapp_number', ''));

        return $number !== '' ? 'https://wa.me/'.$number : null;
    }

    public function phoneUrl(): ?string
    {
        $number = $this->digitsOnly((string) $this->get('business.support_phone', ''));

        return $number !== '' ? 'tel:+'.$number : null;
    }

    private function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
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
