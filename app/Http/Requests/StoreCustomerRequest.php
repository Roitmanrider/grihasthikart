<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-customers') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'max:15', Rule::unique('customers', 'mobile')],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'boolean'],
            'is_premium' => ['nullable', 'boolean'],
            'cashback_enabled' => ['nullable', 'boolean'],
            'monthly_cashback_threshold' => ['nullable', 'numeric', 'min:0'],
            'category_cashback_threshold_percent' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
