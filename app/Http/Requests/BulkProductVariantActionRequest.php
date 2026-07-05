<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProductVariantActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-product-variants') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:product_variants,id'],
            'action' => ['required', 'string', Rule::in(['delete', 'activate', 'deactivate', 'restore'])],
        ];
    }
}
