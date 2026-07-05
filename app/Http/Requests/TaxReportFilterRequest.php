<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaxReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-reports') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'order_status' => ['nullable', 'string', Rule::in(Order::STATUSES)],
            'payment_status' => ['nullable', 'string', Rule::in(Order::PAYMENT_STATUSES)],
            'payment_method' => ['nullable', 'string', Rule::in(Order::PAYMENT_METHODS)],
        ];
    }
}
