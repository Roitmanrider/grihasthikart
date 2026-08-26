<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\DailyOfferRepositoryInterface;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\ProductVariant;
use InvalidArgumentException;

class DailyOfferService
{
    public function __construct(
        private readonly DailyOfferRepositoryInterface $repository
    ) {}

    public function paginate(array $filters = [], int $perPage = 20)
    {
        return $this->repository->paginatedList($filters, $perPage);
    }

    public function currentOffers(int $limit = 8, ?int $stockLocationId = null)
    {
        return $this->repository->currentOffers($limit, $stockLocationId);
    }

    public function productVariantOptions()
    {
        return $this->repository->productVariantOptions();
    }

    public function create(array $data): DailyOffer
    {
        $this->validateBusinessRules($data);

        return $this->repository->create($this->normalize($data));
    }

    public function update(DailyOffer $dailyOffer, array $data): DailyOffer
    {
        $this->validateBusinessRules($data, $dailyOffer->id);

        return $this->repository->update($dailyOffer, $this->normalize($data));
    }

    public function delete(DailyOffer $dailyOffer): bool
    {
        return $this->repository->delete($dailyOffer);
    }

    public function restore(int $id): DailyOffer
    {
        $dailyOffer = $this->repository->findWithTrashed($id);
        $dailyOffer->restore();

        return $dailyOffer;
    }

    private function validateBusinessRules(array $data, ?int $ignoreId = null): void
    {
        $variant = ProductVariant::query()
            ->with('product')
            ->findOrFail((int) $data['product_variant_id']);

        if (! $variant->status || $variant->trashed() || ! $variant->product || ! $variant->product->status || $variant->product->trashed()) {
            throw new InvalidArgumentException('Daily offers can only use active product variants from active products.');
        }

        if ((float) $data['offer_price'] >= (float) $variant->selling_price) {
            throw new InvalidArgumentException('Daily offer price must be lower than the normal selling price.');
        }

        $allocatedQuantity = (float) ($data['allocated_quantity'] ?? 0);

        if ($allocatedQuantity <= 0) {
            throw new InvalidArgumentException('Daily offer allocation must be greater than zero.');
        }

        $productMax = $variant->product?->maximum_order_quantity;
        $offerMax = $data['max_quantity_per_order'] ?? null;

        if ($productMax !== null && $offerMax !== null && $offerMax !== '' && (int) $offerMax > (int) $productMax) {
            throw new InvalidArgumentException('Daily Offer maximum quantity cannot exceed the product maximum of '.$productMax.'.');
        }

        if ($ignoreId !== null) {
            $existing = DailyOffer::query()->find($ignoreId);

            if ($existing && $allocatedQuantity < $existing->soldQuantity()) {
                throw new InvalidArgumentException('Allocated quantity cannot be less than quantity already sold.');
            }
        }

        $stockLocationId = (int) ($data['stock_location_id'] ?? 0);

        if ($stockLocationId <= 0) {
            throw new InvalidArgumentException('Daily offer store is required.');
        }

        if ($offerMax !== null && $offerMax !== '' && (int) $offerMax > $allocatedQuantity) {
            throw new InvalidArgumentException('Daily Offer maximum quantity cannot exceed allocated offer quantity.');
        }

        if ($allocatedQuantity > $this->availableUnallocatedQuantity($variant->id, $stockLocationId, $ignoreId)) {
            throw new InvalidArgumentException('Allocated quantity cannot exceed available unallocated stock.');
        }

        if (($data['starts_at'] ?? null) && ($data['ends_at'] ?? null) && $data['ends_at'] <= $data['starts_at']) {
            throw new InvalidArgumentException('Daily offer end date must be after the start date.');
        }

        if (($data['is_active'] ?? true) && $this->repository->activeOfferExistsForVariant($variant->id, $ignoreId, $data['starts_at'] ?? null, $data['ends_at'] ?? null, $stockLocationId)) {
            throw new InvalidArgumentException('This product variant already has an overlapping active daily offer.');
        }
    }

    private function normalize(array $data): array
    {
        $data['title'] = $data['title'] ?? null;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['display_order'] = $data['display_order'] ?? 1;
        $data['allocated_quantity'] = $data['allocated_quantity'] ?? 0;
        $data['max_quantity_per_order'] = $data['max_quantity_per_order'] ?? 1;
        $data['badge_text'] = $data['badge_text'] ?? null;

        return $data;
    }

    private function availableUnallocatedQuantity(int $productVariantId, int $stockLocationId, ?int $ignoreOfferId = null): float
    {
        $physicalAvailable = (float) Inventory::query()
            ->active()
            ->where('product_variant_id', $productVariantId)
            ->where('stock_location_id', $stockLocationId)
            ->get()
            ->sum('available_quantity');

        $activeAllocated = DailyOffer::query()
            ->active()
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now(config('app.timezone'))))
            ->where('product_variant_id', $productVariantId)
            ->where('stock_location_id', $stockLocationId)
            ->when($ignoreOfferId !== null, fn ($query) => $query->whereKeyNot($ignoreOfferId))
            ->get()
            ->sum(fn (DailyOffer $offer) => max(0, (float) $offer->allocated_quantity - $offer->soldQuantity()));

        return max(0, $physicalAvailable - $activeAllocated);
    }
}
