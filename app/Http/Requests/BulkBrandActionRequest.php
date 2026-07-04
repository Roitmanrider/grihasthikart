<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkBrandActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-brands') ?? false;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'exists:brands,id'],
            'action' => ['required', 'string', Rule::in(['delete', 'activate', 'deactivate', 'restore'])],
        ];
    }
}
