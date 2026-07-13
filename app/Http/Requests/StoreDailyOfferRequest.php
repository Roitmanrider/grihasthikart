<?php

namespace App\Http\Requests;

use App\Models\DailyOffer;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreDailyOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-daily-offers') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'offer_price' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'max_quantity_per_order' => ['nullable', 'integer', 'min:1'],
            'badge_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $variant = ProductVariant::query()->find((int) $this->input('product_variant_id'));

            if (! $variant) {
                return;
            }

            if (! $validator->errors()->has('offer_price') && (float) $this->input('offer_price') >= (float) $variant->selling_price) {
                $validator->errors()->add('offer_price', 'Daily offer price must be lower than the normal selling price.');
            }

            if (! $this->boolean('is_active', true)) {
                return;
            }

            $startsAt = $this->input('starts_at') && ! $validator->errors()->has('starts_at')
                ? Carbon::parse($this->input('starts_at'), config('app.timezone'))
                : null;
            $endsAt = $this->input('ends_at') && ! $validator->errors()->has('ends_at')
                ? Carbon::parse($this->input('ends_at'), config('app.timezone'))
                : null;
            $ignoreId = $this->route('daily_offer')?->id ?? $this->route('dailyOffer')?->id;

            $overlapQuery = DailyOffer::query()
                ->where('product_variant_id', $variant->id)
                ->where('is_active', true)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId));

            if ($startsAt || $endsAt) {
                $overlapQuery->where(function ($query) use ($startsAt, $endsAt) {
                    $query->whereNull('ends_at')
                        ->orWhereNull('starts_at')
                        ->orWhere(function ($query) use ($startsAt, $endsAt) {
                            if ($startsAt && $endsAt) {
                                $query->where('starts_at', '<=', $endsAt)
                                    ->where('ends_at', '>=', $startsAt);
                            } elseif ($startsAt) {
                                $query->where('ends_at', '>=', $startsAt);
                            } elseif ($endsAt) {
                                $query->where('starts_at', '<=', $endsAt);
                            }
                        });
                });
            }

            $overlapExists = $overlapQuery->exists();

            if ($overlapExists) {
                $validator->errors()->add('product_variant_id', 'This product variant already has an overlapping active daily offer.');
            }
        });
    }
}
