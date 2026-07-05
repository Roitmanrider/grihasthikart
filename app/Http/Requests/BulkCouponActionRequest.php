<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkCouponActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-coupons') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', Rule::in(['activate', 'deactivate', 'delete', 'restore'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ];
    }
}
