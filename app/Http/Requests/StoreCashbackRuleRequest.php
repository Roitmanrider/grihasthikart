<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashbackRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-cashback') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'cashback_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'monthly_order_threshold' => ['required', 'numeric', 'min:0'],
            'eligible_category_threshold_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'redemption_multiple' => ['required', 'numeric', 'min:1'],
            'processing_delay_days' => ['required', 'integer', 'min:0', 'max:30'],
            'status' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
