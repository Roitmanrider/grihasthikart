<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkProductImageActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-product-images') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:product_images,id'],
            'action' => ['required', 'string', 'in:delete,restore'],
        ];
    }
}
