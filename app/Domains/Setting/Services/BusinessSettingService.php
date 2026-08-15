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
            'free_delivery_threshold' => $this->get('checkout.free_delivery_threshold', null),
            'premium_minimum_order_amount' => $this->get('checkout.premium_minimum_order_amount', null),
            'premium_delivery_charge' => $this->get('checkout.premium_delivery_charge', null),
            'premium_free_delivery_threshold' => $this->get('checkout.premium_free_delivery_threshold', null),
            'cod_enabled' => (bool) $this->get('payment.cod_enabled', $this->get('checkout.cod_enabled', true)),
            'today_delivery_enabled' => (bool) $this->get('checkout.today_delivery_enabled', true),
            'today_delivery_cutoff_time' => $this->get('checkout.today_delivery_cutoff_time', '14:00'),
            'custom_delivery_date_enabled' => (bool) $this->get('checkout.custom_delivery_date_enabled', true),
            'max_delivery_days_ahead' => (int) $this->get('checkout.max_delivery_days_ahead', 7),
            'cart_hold_minutes' => (int) $this->get('checkout.cart_hold_minutes', 60),
            'cart_reminder_enabled' => filter_var($this->get('checkout.cart_reminder_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'cart_reminder_minutes' => (int) $this->get('checkout.cart_reminder_minutes', 30),
            'cart_whatsapp_reminder_enabled' => filter_var($this->get('checkout.cart_whatsapp_reminder_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'cart_whatsapp_reminder_minutes' => (int) $this->get('checkout.cart_whatsapp_reminder_minutes', 45),
            'cart_employee_followup_enabled' => filter_var($this->get('checkout.cart_employee_followup_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'cart_abuse_monitoring_enabled' => filter_var($this->get('checkout.cart_abuse_monitoring_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'daily_offer_hold_minutes' => (int) $this->get('checkout.daily_offer_hold_minutes', 15),
            'customer_credit_redemption_enabled' => filter_var($this->get('checkout.customer_credit_redemption_enabled', true), FILTER_VALIDATE_BOOLEAN),
            'default_state' => $this->get('checkout.default_state'),
            'default_city' => $this->get('checkout.default_city'),
            'store_contact_mobile' => $this->get('checkout.store_contact_mobile'),
            'store_whatsapp_number' => $this->get('checkout.store_whatsapp_number'),
            'customer_invoice_enabled' => (bool) $this->get('order.customer_invoice_enabled', true),
            'return_window_days' => (int) $this->get('order.return_window_days', 2),
        ];
    }

    public function storefrontSettings(): array
    {
        return [
            'access_mode' => $this->get('storefront.access_mode', 'PUBLIC_BROWSE_MEMBERS_BUY'),
            'homepage_public_in_members_only' => filter_var($this->get('storefront.homepage_public_in_members_only', true), FILTER_VALIDATE_BOOLEAN),
            'allow_guest_checkout' => filter_var($this->get('storefront.allow_guest_checkout', false), FILTER_VALIDATE_BOOLEAN),
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
        $metadata = [
            'minimum_order_amount' => ['decimal', 'Standard Minimum Order Amount', 1],
            'delivery_charge' => ['decimal', 'Standard Delivery Charge', 2],
            'free_delivery_threshold' => ['decimal', 'Standard Free Delivery Threshold', 3],
            'premium_minimum_order_amount' => ['decimal', 'Premium Minimum Order Amount', 4],
            'premium_delivery_charge' => ['decimal', 'Premium Delivery Charge', 5],
            'premium_free_delivery_threshold' => ['decimal', 'Premium Free Delivery Threshold', 6],
            'cart_hold_minutes' => ['integer', 'Cart Hold Duration', 8],
            'cart_reminder_enabled' => ['boolean', 'Customer In-App Cart Reminder', 9],
            'cart_reminder_minutes' => ['integer', 'In-App Cart Reminder After', 10],
            'cart_whatsapp_reminder_enabled' => ['boolean', 'Automatic WhatsApp Cart Reminder', 11],
            'cart_whatsapp_reminder_minutes' => ['integer', 'WhatsApp Cart Reminder After', 12],
            'cart_employee_followup_enabled' => ['boolean', 'Employee Cart Follow-up', 13],
            'cart_abuse_monitoring_enabled' => ['boolean', 'Abuse / Reservation Monitoring', 14],
            'daily_offer_hold_minutes' => ['integer', 'Daily Offer Reservation Duration', 15],
            'customer_credit_redemption_enabled' => ['boolean', 'Customer Credit Redemption', 16],
            'return_window_days' => ['integer', 'Return Window Days', 17],
        ];

        foreach ($data as $key => $value) {
            if ($key === 'customer_invoice_enabled') {
                $this->set('order.customer_invoice_enabled', $value);

                continue;
            }

            if ($key === 'return_window_days') {
                $this->set('order.return_window_days', $value)
                    ->update([
                        'value_type' => 'integer',
                        'label' => 'Return Window Days',
                        'display_order' => 2,
                    ]);

                continue;
            }

            $setting = $this->set('checkout.'.$key, $value);

            if (isset($metadata[$key])) {
                [$type, $label, $order] = $metadata[$key];
                $setting->update([
                    'value_type' => $type,
                    'label' => $label,
                    'display_order' => $order,
                ]);
            }
        }
    }

    public function updateStorefrontSettings(array $data): void
    {
        $this->set('storefront.access_mode', $data['access_mode'])
            ->update([
                'value_type' => 'string',
                'label' => 'Storefront Access Mode',
                'display_order' => 1,
            ]);

        $this->set('storefront.homepage_public_in_members_only', (bool) $data['homepage_public_in_members_only'])
            ->update([
                'value_type' => 'boolean',
                'label' => 'Homepage Public in Members-Only Mode',
                'display_order' => 2,
            ]);

        $this->set('storefront.allow_guest_checkout', (bool) $data['allow_guest_checkout'])
            ->update([
                'value_type' => 'boolean',
                'label' => 'Allow Guest Checkout',
                'display_order' => 3,
            ]);
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
            'decimal' => $value === null ? null : (float) $value,
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
