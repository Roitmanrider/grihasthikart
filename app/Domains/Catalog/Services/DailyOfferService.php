<?php

namespace App\Domains\Catalog\Services;

use App\Domains\Catalog\Contracts\DailyOfferRepositoryInterface;
use App\Models\DailyOffer;
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

    public function currentOffers(int $limit = 8)
    {
        return $this->repository->currentOffers($limit);
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

        if ((float) $data['offer_price'] > (float) $variant->mrp) {
            throw new InvalidArgumentException('Daily offer price cannot be greater than the variant MRP.');
        }

        if (($data['starts_at'] ?? null) && ($data['ends_at'] ?? null) && $data['ends_at'] <= $data['starts_at']) {
            throw new InvalidArgumentException('Daily offer end date must be after the start date.');
        }

        if (($data['is_active'] ?? true) && $this->repository->activeOfferExistsForVariant($variant->id, $ignoreId)) {
            throw new InvalidArgumentException('This product variant already has an active daily offer.');
        }
    }

    private function normalize(array $data): array
    {
        $data['title'] = $data['title'] ?? null;
        $data['starts_at'] = $data['starts_at'] ?? null;
        $data['ends_at'] = $data['ends_at'] ?? null;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['max_quantity_per_order'] = $data['max_quantity_per_order'] ?? null;
        $data['badge_text'] = $data['badge_text'] ?? null;

        return $data;
    }
}
