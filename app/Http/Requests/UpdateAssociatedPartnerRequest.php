<?php

namespace App\Http\Requests;

class UpdateAssociatedPartnerRequest extends StoreAssociatedPartnerRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }
}
