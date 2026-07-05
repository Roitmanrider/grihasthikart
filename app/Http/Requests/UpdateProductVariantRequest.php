<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'attribute_values' => array_filter($this->input('attribute_values', [])),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-product-variants') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $variant = $this->route('productVariant');

        return [
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variant?->id)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'barcode')->ignore($variant?->id)],
            'variant_name' => ['required', 'string', 'max:255'],
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => ['integer', 'distinct', 'exists:attribute_values,id'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'mrp' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0', 'lte:mrp'],
            'purchase_price' => ['nullable', 'numeric', 'min:0', 'lte:mrp'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
