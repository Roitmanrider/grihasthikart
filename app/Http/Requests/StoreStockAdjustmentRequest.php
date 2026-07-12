<?php

namespace App\Http\Requests;

use App\Models\StockAdjustment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-inventory') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'adjustment_type' => ['required', 'string', Rule::in(StockAdjustment::TYPES)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', Rule::in(StockAdjustment::REASONS)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'adjustment_date' => ['required', 'date'],
        ];
    }
}
