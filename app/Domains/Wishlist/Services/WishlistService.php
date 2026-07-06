<?php

namespace App\Domains\Wishlist\Services;

use App\Domains\Wishlist\Contracts\WishlistRepositoryInterface;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WishlistService
{
    public function __construct(
        private readonly WishlistRepositoryInterface $repository
    ) {}

    public function itemsForCustomer(Customer $customer, int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->forCustomer($customer, $perPage);
    }

    public function add(Customer $customer, int $productVariantId): WishlistItem
    {
        return DB::transaction(function () use ($customer, $productVariantId) {
            $variant = ProductVariant::query()
                ->with('product')
                ->findOrFail($productVariantId);

            $this->validateVariantCanBeWishlisted($variant);

            $existingItem = $this->repository->findExistingForCustomer($customer, $variant->id);

            if ($existingItem) {
                if ($existingItem->trashed()) {
                    $existingItem->restore();
                }

                if ((int) $existingItem->product_id !== (int) $variant->product_id) {
                    $existingItem->update(['product_id' => $variant->product_id]);
                }

                return $existingItem;
            }

            return $this->repository->createForCustomer($customer, $variant);
        });
    }

    public function remove(Customer $customer, WishlistItem $wishlistItem): bool
    {
        return DB::transaction(fn () => $this->repository->removeForCustomer($customer, $wishlistItem));
    }

    public function countForCustomer(?Customer $customer): int
    {
        return $customer ? $this->repository->countForCustomer($customer) : 0;
    }

    public function isWishlisted(Customer $customer, int $productVariantId): bool
    {
        $item = $this->repository->findExistingForCustomer($customer, $productVariantId);

        return $item !== null && ! $item->trashed();
    }

    private function validateVariantCanBeWishlisted(ProductVariant $variant): void
    {
        if (! $variant->status || $variant->trashed()) {
            throw new InvalidArgumentException('This product variant is not available.');
        }

        if (! $variant->product || ! $variant->product->status || $variant->product->trashed()) {
            throw new InvalidArgumentException('This product is not available.');
        }
    }
}
