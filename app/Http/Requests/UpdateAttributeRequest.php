<?php

namespace App\Http\Requests;

use App\Models\Attribute;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-attributes') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $attributeId = $this->route('attribute')?->id;

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('attributes', 'name')->ignore($attributeId)],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('attributes', 'slug')->ignore($attributeId)],
            'type' => ['required', 'string', Rule::in(Attribute::TYPES)],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_variant_defining' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
