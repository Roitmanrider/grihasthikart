<?php

namespace App\Http\Requests;

class UpdateHomepageBannerRequest extends StoreHomepageBannerRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'desktop_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_mobile_image' => ['nullable', 'boolean'],
        ]);
    }
}
