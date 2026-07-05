<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-payment-settings') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'cod_enabled' => ['nullable', 'boolean'],
            'qr_enabled' => ['nullable', 'boolean'],
            'razorpay_enabled' => ['nullable', 'boolean'],
            'qr_label' => ['required', 'string', 'max:100'],
            'qr_upi_id' => ['nullable', 'string', 'max:100'],
            'qr_display_name' => ['nullable', 'string', 'max:100'],
            'qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'razorpay_key_id' => ['nullable', 'string', 'max:255'],
            'razorpay_key_secret' => ['nullable', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
        ];
    }
}
