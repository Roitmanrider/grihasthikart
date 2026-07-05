<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectCashbackRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-cashback') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'admin_note' => ['required', 'string', 'max:1000'],
        ];
    }
}
