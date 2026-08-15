<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateInventoryRequest extends FormRequest
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
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'target_stock_level' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $reorderLevel = $this->input('reorder_level');
                $targetStock = $this->input('target_stock_level');

                if ($reorderLevel !== null && $reorderLevel !== '' && $targetStock !== null && $targetStock !== '' && (float) $targetStock < (float) $reorderLevel) {
                    $validator->errors()->add('target_stock_level', 'Target stock level must be greater than or equal to reorder level.');
                }
            },
        ];
    }
}
