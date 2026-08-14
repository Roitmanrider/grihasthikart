<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBusinessSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $allowedDurations = [15, 30, 45, 60, 75, 90, 105, 120];

        return [
            'minimum_order_amount' => ['required', 'numeric', 'min:0'],
            'delivery_charge' => ['required', 'numeric', 'min:0'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'premium_minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'premium_delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'premium_free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'cod_enabled' => ['nullable', 'boolean'],
            'today_delivery_enabled' => ['nullable', 'boolean'],
            'today_delivery_cutoff_time' => ['required', 'date_format:H:i'],
            'custom_delivery_date_enabled' => ['nullable', 'boolean'],
            'max_delivery_days_ahead' => ['required', 'integer', 'min:0', 'max:60'],
            'cart_hold_minutes' => ['required', 'integer', Rule::in($allowedDurations)],
            'cart_reminder_enabled' => ['nullable', 'boolean'],
            'cart_reminder_minutes' => ['required', 'integer', Rule::in($allowedDurations)],
            'cart_whatsapp_reminder_enabled' => ['nullable', 'boolean'],
            'cart_whatsapp_reminder_minutes' => ['required', 'integer', Rule::in($allowedDurations)],
            'cart_employee_followup_enabled' => ['nullable', 'boolean'],
            'cart_abuse_monitoring_enabled' => ['nullable', 'boolean'],
            'daily_offer_hold_minutes' => ['required', 'integer', Rule::in($allowedDurations)],
            'default_state' => ['nullable', 'string', 'max:100'],
            'default_city' => ['nullable', 'string', 'max:100'],
            'store_contact_mobile' => ['nullable', 'string', 'max:15'],
            'store_whatsapp_number' => ['nullable', 'string', 'max:15'],
            'customer_invoice_enabled' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hold = (int) $this->input('cart_hold_minutes');
            $reminder = (int) $this->input('cart_reminder_minutes');
            $whatsapp = (int) $this->input('cart_whatsapp_reminder_minutes');
            $reminderEnabled = $this->boolean('cart_reminder_enabled');
            $whatsappEnabled = $this->boolean('cart_whatsapp_reminder_enabled');

            if ($reminderEnabled && $reminder >= $hold) {
                $validator->errors()->add('cart_reminder_minutes', 'The in-app reminder must be before the cart hold expires.');
            }

            if ($whatsappEnabled && $whatsapp >= $hold) {
                $validator->errors()->add('cart_whatsapp_reminder_minutes', 'The WhatsApp reminder must be before the cart hold expires.');
            }

            if ($reminderEnabled && $whatsappEnabled && $reminder >= $whatsapp) {
                $validator->errors()->add('cart_whatsapp_reminder_minutes', 'The WhatsApp reminder must be after the in-app reminder.');
            }
        });
    }
}
