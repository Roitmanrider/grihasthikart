<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchaseEntryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn (array $item) => ! empty($item['product_variant_id']))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-inventory') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supplierRule = ['nullable', 'integer'];

        if (Schema::hasTable('suppliers')) {
            $supplierRule[] = Rule::exists('suppliers', 'id')->where('status', 'active');
        }

        return [
            'supplier_id' => $supplierRule,
            'bill_number' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
            'freight_allocation' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.gst_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.cgst_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.sgst_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_number' => ['nullable', 'string', 'max:255'],
            'items.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('supplier_id') && ! Schema::hasTable('suppliers')) {
                    $validator->errors()->add('supplier_id', 'Supplier module is not available.');
                }

                $variantIds = collect($this->input('items', []))
                    ->pluck('product_variant_id')
                    ->filter()
                    ->map(fn ($id) => (string) $id);

                if ($variantIds->count() !== $variantIds->unique()->count()) {
                    $validator->errors()->add('items', 'Duplicate variants are not allowed in one purchase entry.');
                }

                collect($this->input('items', []))->each(function (array $item, int $index) use ($validator): void {
                    $base = (float) ($item['quantity'] ?? 0) * (float) ($item['purchase_price'] ?? 0);
                    $discount = (float) ($item['discount_amount'] ?? 0);

                    if ($discount > $base) {
                        $validator->errors()->add("items.$index.discount_amount", 'Discount cannot be greater than line base amount.');
                    }
                });
            },
        ];
    }
}
