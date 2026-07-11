<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'minimum_order_amount' => ['required', 'numeric', 'min:0'],
            'delivery_charge' => ['required', 'numeric', 'min:0'],
            'cod_enabled' => ['nullable', 'boolean'],
            'today_delivery_enabled' => ['nullable', 'boolean'],
            'today_delivery_cutoff_time' => ['required', 'date_format:H:i'],
            'custom_delivery_date_enabled' => ['nullable', 'boolean'],
            'max_delivery_days_ahead' => ['required', 'integer', 'min:0', 'max:60'],
            'default_state' => ['nullable', 'string', 'max:100'],
            'default_city' => ['nullable', 'string', 'max:100'],
            'store_contact_mobile' => ['nullable', 'string', 'max:15'],
            'store_whatsapp_number' => ['nullable', 'string', 'max:15'],
            'customer_invoice_enabled' => ['nullable', 'boolean'],
        ];
    }
}
