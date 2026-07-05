<?php

namespace App\Http\Requests;

use App\Models\Coupon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => str($this->input('code'))->upper()->replaceMatches('/\s+/', '')->toString()]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-coupons') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $coupon = $this->route('coupon');

        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('coupons', 'code')->ignore($coupon)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'discount_type' => ['required', 'string', Rule::in(Coupon::DISCOUNT_TYPES)],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit_total' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_session' => ['nullable', 'integer', 'min:1'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['nullable', 'boolean'],
            'is_cashback_coupon' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', Rule::in(Coupon::SOURCES)],
        ];
    }
}
