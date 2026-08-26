<?php

namespace App\Http\Requests;

use App\Domains\Store\Services\AdminStoreContextService;
use App\Models\DailyOffer;
use App\Models\ProductVariant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreDailyOfferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('stock_location_id')) {
            return;
        }

        $routeOffer = $this->route('daily_offer') ?? $this->route('dailyOffer');
        $storeId = $routeOffer instanceof DailyOffer
            ? $routeOffer->stock_location_id
            : $this->session()->get(AdminStoreContextService::SESSION_KEY);

        if ($storeId) {
            $this->merge(['stock_location_id' => $storeId]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage-daily-offers') ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'stock_location_id' => ['required', 'integer', 'exists:stock_locations,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'offer_price' => ['required', 'numeric', 'min:0'],
            'allocated_quantity' => ['required', 'numeric', 'gt:0'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'display_order' => ['required', 'integer', 'min:1'],
            'max_quantity_per_order' => ['required', 'integer', 'min:1'],
            'badge_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $variant = ProductVariant::query()->find((int) $this->input('product_variant_id'));
            $existingOffer = $this->route('daily_offer') ?? $this->route('dailyOffer');
            $ignoreId = $existingOffer?->id;
            $now = Carbon::now(config('app.timezone'));

            if (! $variant) {
                return;
            }

            if ($existingOffer instanceof DailyOffer && $existingOffer->lifecycleState() === 'Expired') {
                $validator->errors()->add('daily_offer', 'Expired Daily Offers are read-only. Duplicate it as a new offer instead.');

                return;
            }

            if (! $validator->errors()->has('offer_price') && (float) $this->input('offer_price') >= (float) $variant->selling_price) {
                $validator->errors()->add('offer_price', 'Daily offer price must be lower than the normal selling price.');
            }

            $productMax = $variant->product?->maximum_order_quantity;
            $offerMax = $this->input('max_quantity_per_order');

            if ($productMax !== null && $offerMax !== null && $offerMax !== '' && (int) $offerMax > (int) $productMax) {
                $validator->errors()->add('max_quantity_per_order', 'Daily Offer maximum quantity cannot exceed the product maximum of '.$productMax.'.');
            }

            $startsAt = $this->input('starts_at') && ! $validator->errors()->has('starts_at')
                ? Carbon::parse($this->input('starts_at'), config('app.timezone'))
                : null;
            $endsAt = $this->input('ends_at') && ! $validator->errors()->has('ends_at')
                ? Carbon::parse($this->input('ends_at'), config('app.timezone'))
                : null;

            if ((int) $this->input('max_quantity_per_order') > (float) $this->input('allocated_quantity')) {
                $validator->errors()->add('max_quantity_per_order', 'Daily Offer maximum quantity cannot exceed allocated offer quantity.');
            }

            if ($startsAt) {
                $startsAtChanged = ! $existingOffer instanceof DailyOffer
                    || ! $existingOffer->starts_at
                    || ! $existingOffer->starts_at->timezone(config('app.timezone'))->equalTo($startsAt);

                if ($startsAtChanged && $startsAt->lt($now->copy()->subMinutes(2))) {
                    $validator->errors()->add('starts_at', 'Daily Offer start time must be current or future.');
                }
            }

            if ($endsAt && $endsAt->lte($now)) {
                $validator->errors()->add('ends_at', 'Daily Offer end time must be in the future.');
            }

            if (! $this->boolean('is_active', true)) {
                return;
            }

            $stockLocationId = (int) $this->input('stock_location_id');

            if ($validator->errors()->has('starts_at') || $validator->errors()->has('ends_at')) {
                return;
            }

            $overlapQuery = DailyOffer::query()
                ->where('product_variant_id', $variant->id)
                ->where('stock_location_id', $stockLocationId)
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

            $displayOrderExists = DailyOffer::query()
                ->where('stock_location_id', $stockLocationId)
                ->where('display_order', (int) $this->input('display_order'))
                ->where('is_active', true)
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now))
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where(function ($query) use ($startsAt, $endsAt) {
                    $query->whereNull('ends_at')
                        ->orWhereNull('starts_at')
                        ->orWhere(function ($query) use ($startsAt, $endsAt) {
                            $query->where('starts_at', '<=', $endsAt)
                                ->where('ends_at', '>=', $startsAt);
                        });
                })
                ->exists();

            if ($displayOrderExists) {
                $validator->errors()->add('display_order', 'Display order is already used by another overlapping Daily Offer for this store.');
            }
        });
    }
}
