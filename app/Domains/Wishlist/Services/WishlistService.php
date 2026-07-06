<?php

namespace App\Domains\Wishlist\Services;

use App\Domains\Cart\Services\CartService;
use App\Domains\Wishlist\Contracts\WishlistRepositoryInterface;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WishlistService
{
    /** @var array<int, array<int>> */
    private array $variantIdCache = [];

    public function __construct(
        private readonly WishlistRepositoryInterface $repository,
        private readonly CartService $cartService
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

                $this->clearCustomerCache($customer);

                return $existingItem;
            }

            $item = $this->repository->createForCustomer($customer, $variant);
            $this->clearCustomerCache($customer);

            return $item;
        });
    }

    public function remove(Customer $customer, WishlistItem $wishlistItem): bool
    {
        return DB::transaction(function () use ($customer, $wishlistItem) {
            $removed = $this->repository->removeForCustomer($customer, $wishlistItem);
            $this->clearCustomerCache($customer);

            return $removed;
        });
    }

    public function moveToCart(Customer $customer, WishlistItem $wishlistItem, string $sessionId): void
    {
        DB::transaction(function () use ($customer, $wishlistItem, $sessionId) {
            $wishlistItem = $this->repository->findForCustomer($customer, $wishlistItem->id);

            $this->cartService->addItem($sessionId, (int) $wishlistItem->product_variant_id, 1);
            $this->repository->removeForCustomer($customer, $wishlistItem);
            $this->clearCustomerCache($customer);
        });
    }

    public function countForCustomer(?Customer $customer): int
    {
        return $customer ? $this->repository->countForCustomer($customer) : 0;
    }

    public function isWishlisted(Customer $customer, int $productVariantId): bool
    {
        return in_array($productVariantId, $this->activeVariantIdsForCustomer($customer), true);
    }

    public function activeVariantIdsForCustomer(?Customer $customer): array
    {
        if (! $customer) {
            return [];
        }

        return $this->variantIdCache[$customer->id]
            ??= $this->repository->activeVariantIdsForCustomer($customer);
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

    private function clearCustomerCache(Customer $customer): void
    {
        unset($this->variantIdCache[$customer->id]);
    }
}
