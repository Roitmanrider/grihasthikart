<?php

namespace App\Http\Requests;

class CreateRazorpayOrderRequest extends PlaceOrderRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'payment_method' => ['required', 'string', 'in:razorpay'],
        ]);
    }
}
