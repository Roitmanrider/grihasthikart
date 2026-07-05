<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-attribute-values') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'value' => [
                'required',
                'string',
                'max:150',
                Rule::unique('attribute_values', 'value')
                    ->where(fn ($query) => $query->where('attribute_id', $this->input('attribute_id'))),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('attribute_values', 'slug')
                    ->where(fn ($query) => $query->where('attribute_id', $this->input('attribute_id'))),
            ],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }
}
