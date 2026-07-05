<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_mobile' => ['required', 'string', 'min:10', 'max:15'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_address_line1' => ['required', 'string', 'max:255'],
            'delivery_address_line2' => ['nullable', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:100'],
            'delivery_state' => ['required', 'string', 'max:100'],
            'delivery_pincode' => ['required', 'string', 'max:10'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'delivery_slot' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
