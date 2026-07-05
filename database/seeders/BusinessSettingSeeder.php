<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['checkout', 'minimum_order_amount', '0', 'decimal', 'Minimum Order Amount', 1],
            ['checkout', 'delivery_charge', '0', 'decimal', 'Delivery Charge', 2],
            ['checkout', 'cod_enabled', '1', 'boolean', 'COD Enabled', 3],
            ['checkout', 'today_delivery_enabled', '1', 'boolean', 'Today Delivery Enabled', 4],
            ['checkout', 'today_delivery_cutoff_time', '14:00', 'string', 'Today Delivery Cutoff Time', 5],
            ['checkout', 'custom_delivery_date_enabled', '1', 'boolean', 'Custom Delivery Date Enabled', 6],
            ['checkout', 'max_delivery_days_ahead', '7', 'integer', 'Max Delivery Days Ahead', 7],
            ['checkout', 'default_state', null, 'string', 'Default State', 8],
            ['checkout', 'default_city', null, 'string', 'Default City', 9],
            ['checkout', 'store_contact_mobile', null, 'string', 'Store Contact Mobile', 10],
            ['checkout', 'store_whatsapp_number', null, 'string', 'Store WhatsApp Number', 11],
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
            ['tax', 'prices_include_gst', '1', 'boolean', 'Prices Include GST', 1],
            ['tax', 'default_gst_rate', '0', 'decimal', 'Default GST Rate', 2],
            ['tax', 'company_gstin', null, 'string', 'Company GSTIN', 3],
            ['tax', 'company_legal_name', null, 'string', 'Company Legal Name', 4],
            ['tax', 'company_address', null, 'string', 'Company Address', 5],
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
