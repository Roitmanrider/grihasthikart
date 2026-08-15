<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssociatedPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'external_url' => ['nullable', 'string', 'max:255', $this->safeUrlRule()],
            'promo_text' => ['nullable', 'string', 'max:80'],
            'enabled' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
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
