<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestCashbackRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'requested_amount' => ['required', 'numeric', 'min:1'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
