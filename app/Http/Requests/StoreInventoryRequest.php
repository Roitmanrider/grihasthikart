<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
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
            'product_variant_id' => [
                'required',
                'integer',
                'exists:product_variants,id',
                Rule::unique('inventories', 'product_variant_id')
                    ->where('stock_location_id', $this->input('stock_location_id')),
            ],
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'quantity_on_hand' => ['nullable', 'numeric', 'min:0'],
            'reserved_quantity' => ['nullable', 'numeric', 'min:0'],
            'damaged_quantity' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'target_stock_level' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
