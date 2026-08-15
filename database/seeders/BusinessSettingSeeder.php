<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['checkout', 'minimum_order_amount', '0', 'decimal', 'Standard Minimum Order Amount', 1],
            ['checkout', 'delivery_charge', '0', 'decimal', 'Standard Delivery Charge', 2],
            ['checkout', 'free_delivery_threshold', null, 'decimal', 'Standard Free Delivery Threshold', 3],
            ['checkout', 'premium_minimum_order_amount', null, 'decimal', 'Premium Minimum Order Amount', 4],
            ['checkout', 'premium_delivery_charge', null, 'decimal', 'Premium Delivery Charge', 5],
            ['checkout', 'premium_free_delivery_threshold', null, 'decimal', 'Premium Free Delivery Threshold', 6],
            ['checkout', 'cod_enabled', '1', 'boolean', 'COD Enabled', 7],
            ['checkout', 'today_delivery_enabled', '1', 'boolean', 'Today Delivery Enabled', 8],
            ['checkout', 'today_delivery_cutoff_time', '14:00', 'string', 'Today Delivery Cutoff Time', 9],
            ['checkout', 'custom_delivery_date_enabled', '1', 'boolean', 'Custom Delivery Date Enabled', 10],
            ['checkout', 'max_delivery_days_ahead', '7', 'integer', 'Max Delivery Days Ahead', 11],
            ['checkout', 'cart_hold_minutes', '60', 'integer', 'Cart Hold Duration', 8],
            ['checkout', 'cart_reminder_enabled', '1', 'boolean', 'Customer In-App Cart Reminder', 9],
            ['checkout', 'cart_reminder_minutes', '30', 'integer', 'In-App Cart Reminder After', 10],
            ['checkout', 'cart_whatsapp_reminder_enabled', '0', 'boolean', 'Automatic WhatsApp Cart Reminder', 11],
            ['checkout', 'cart_whatsapp_reminder_minutes', '45', 'integer', 'WhatsApp Cart Reminder After', 12],
            ['checkout', 'cart_employee_followup_enabled', '1', 'boolean', 'Employee Cart Follow-up', 13],
            ['checkout', 'cart_abuse_monitoring_enabled', '1', 'boolean', 'Abuse / Reservation Monitoring', 14],
            ['checkout', 'daily_offer_hold_minutes', '15', 'integer', 'Daily Offer Reservation Duration', 15],
            ['checkout', 'customer_credit_redemption_enabled', '1', 'boolean', 'Customer Credit Redemption', 16],
            ['checkout', 'default_state', null, 'string', 'Default State', 16],
            ['checkout', 'default_city', null, 'string', 'Default City', 17],
            ['checkout', 'store_contact_mobile', null, 'string', 'Store Contact Mobile', 18],
            ['checkout', 'store_whatsapp_number', null, 'string', 'Store WhatsApp Number', 19],
            ['payment', 'cod_enabled', '1', 'boolean', 'COD Enabled', 1],
            ['payment', 'qr_enabled', '0', 'boolean', 'QR Payment Enabled', 2],
            ['payment', 'razorpay_enabled', '0', 'boolean', 'Razorpay Enabled', 3],
            ['payment', 'qr_label', 'Pay by QR', 'string', 'QR Payment Label', 4],
            ['payment', 'qr_upi_id', null, 'string', 'QR UPI ID', 5],
            ['payment', 'qr_display_name', null, 'string', 'QR Display Name', 6],
            ['payment', 'qr_image_path', null, 'string', 'QR Image Path', 7],
            ['payment', 'razorpay_key_id', null, 'string', 'Razorpay Key ID', 8],
            ['payment', 'razorpay_key_secret', null, 'string', 'Razorpay Key Secret', 9],
            ['payment', 'currency', 'INR', 'string', 'Currency', 10],
            ['order', 'customer_invoice_enabled', '1', 'boolean', 'Customer Invoice Printing Enabled', 1],
            ['order', 'return_window_days', '2', 'integer', 'Return Window Days', 2],
            ['storefront', 'access_mode', 'PUBLIC_BROWSE_MEMBERS_BUY', 'string', 'Storefront Access Mode', 1],
            ['storefront', 'homepage_public_in_members_only', '1', 'boolean', 'Homepage Public in Members-Only Mode', 2],
            ['storefront', 'allow_guest_checkout', '0', 'boolean', 'Allow Guest Checkout', 3],
            ['tax', 'prices_include_gst', '1', 'boolean', 'Prices Include GST', 1],
            ['tax', 'default_gst_rate', '0', 'decimal', 'Default GST Rate', 2],
            ['tax', 'company_gstin', null, 'string', 'Company GSTIN', 3],
            ['tax', 'company_legal_name', null, 'string', 'Company Legal Name', 4],
            ['tax', 'company_address', null, 'string', 'Company Address', 5],
            ['business', 'name', 'GrihasthiKart', 'string', 'Business Name', 1],
            ['business', 'support_email', 'support@grihasthikart.in', 'string', 'Support Email', 2],
            ['business', 'support_phone', null, 'string', 'Support Phone', 3],
            ['business', 'whatsapp_number', null, 'string', 'WhatsApp Number', 4],
            ['business', 'address', null, 'string', 'Address', 5],
            ['business', 'city', null, 'string', 'City', 6],
            ['business', 'state', null, 'string', 'State', 7],
            ['business', 'pincode', null, 'string', 'Pincode', 8],
            ['business', 'instagram_url', null, 'string', 'Instagram URL', 9],
            ['business', 'business_hours', 'Daily, 9 AM - 8 PM', 'string', 'Business Hours', 10],
            ['business', 'google_maps_url', null, 'string', 'Google Maps URL', 11],
        ];

        foreach ($settings as [$group, $key, $value, $type, $label, $order]) {
            BusinessSetting::query()->updateOrCreate(
                ['group' => $group, 'key' => $key],
                [
                    'value' => $value,
                    'value_type' => $type,
                    'label' => $label,
                    'is_public' => false,
                    'is_editable' => true,
                    'display_order' => $order,
                ]
            );
        }
    }
}
