<?php

namespace App\Http\Requests;

use App\Domains\Storefront\Services\StorefrontAccessService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStorefrontSeoSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-settings') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'access_mode' => ['required', 'string', 'in:PUBLIC_STOREFRONT,PUBLIC_BROWSE_MEMBERS_BUY,MEMBERS_ONLY_STOREFRONT'],
            'homepage_public_in_members_only' => ['nullable', 'boolean'],
            'allow_guest_checkout' => ['nullable', 'boolean'],
            'guest_checkout_acknowledged' => ['nullable'],
            'members_only_seo_acknowledged' => ['nullable'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->sometimes(
            'guest_checkout_acknowledged',
            ['accepted'],
            fn () => $this->input('access_mode') === StorefrontAccessService::PUBLIC_STOREFRONT
                && (bool) $this->boolean('allow_guest_checkout')
        );

        $validator->sometimes(
            'members_only_seo_acknowledged',
            ['accepted'],
            fn () => $this->input('access_mode') === StorefrontAccessService::MEMBERS_ONLY_STOREFRONT
        );
    }
}
