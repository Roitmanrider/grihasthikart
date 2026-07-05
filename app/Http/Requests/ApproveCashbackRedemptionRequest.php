<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveCashbackRedemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-cashback') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:1'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
