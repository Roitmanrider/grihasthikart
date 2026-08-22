<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomepageBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:120'],
            'stock_location_id' => ['nullable', 'integer', 'exists:stock_locations,id'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'cta_text' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'open_in_new_tab' => ['nullable', 'boolean'],
            'alt_text' => ['nullable', 'string', 'max:150'],
            'desktop_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'enabled' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function safeUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '' || str_starts_with((string) $value, '/')) {
                return;
            }

            $scheme = parse_url((string) $value, PHP_URL_SCHEME);

            if (! in_array($scheme, ['http', 'https'], true)) {
                $fail('The '.$attribute.' must be a relative URL or an http/https URL.');
            }
        };
    }
}
