<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'stock_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_icon' => ['nullable', 'boolean'],
            'enabled' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'desktop_item_limit' => ['required', 'integer', 'min:1', 'max:24'],
            'mobile_item_limit' => ['nullable', 'integer', 'min:1', 'max:24'],
            'source_mode' => ['nullable', 'in:automatic,manual'],
            'root_category_id' => ['nullable', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'view_all_enabled' => ['nullable', 'boolean'],
            'view_all_text' => ['nullable', 'string', 'max:60'],
            'view_all_url' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
        ];
    }

    private function safeUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            if (str_starts_with((string) $value, '/')) {
                return;
            }

            $scheme = parse_url((string) $value, PHP_URL_SCHEME);

            if (! in_array($scheme, ['http', 'https'], true)) {
                $fail('The '.$attribute.' must be a relative URL or an http/https URL.');
            }
        };
    }
}
