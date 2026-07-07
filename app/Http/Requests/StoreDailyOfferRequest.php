<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDailyOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-daily-offers') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'offer_price' => ['required', 'numeric', 'gt:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'max_quantity_per_order' => ['nullable', 'integer', 'min:1'],
            'badge_text' => ['nullable', 'string', 'max:255'],
        ];
    }
}
