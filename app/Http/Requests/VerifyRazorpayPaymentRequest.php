<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRazorpayPaymentRequest extends FormRequest
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
            'razorpay_order_id' => ['required', 'string', 'max:255'],
            'razorpay_payment_id' => ['required', 'string', 'max:255'],
            'razorpay_signature' => ['required', 'string', 'max:255'],
        ];
    }
}
