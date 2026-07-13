<?php

namespace App\Http\Requests;

use App\Domains\Catalog\Services\ProductCatalogImportExportService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewProductImportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'duplicate_action' => $this->input('duplicate_action', ProductCatalogImportExportService::DUPLICATE_UPDATE),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-product-imports') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:51200'],
            'duplicate_action' => ['required', Rule::in(ProductCatalogImportExportService::DUPLICATE_ACTIONS)],
        ];
    }
}
