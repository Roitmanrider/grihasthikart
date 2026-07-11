<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class FailRazorpayPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'order_number' => ['required', 'string', 'max:255'],
            'razorpay_order_id' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
