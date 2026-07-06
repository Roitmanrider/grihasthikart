<?php

namespace App\Domains\Wishlist\Contracts;

use App\Core\Contracts\RepositoryInterface;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WishlistRepositoryInterface extends RepositoryInterface
{
    public function forCustomer(Customer $customer, int $perPage = 12): LengthAwarePaginator;

    public function findForCustomer(Customer $customer, int $wishlistItemId): WishlistItem;

    public function findExistingForCustomer(Customer $customer, int $productVariantId): ?WishlistItem;

    public function createForCustomer(Customer $customer, ProductVariant $variant): WishlistItem;

    public function removeForCustomer(Customer $customer, WishlistItem $wishlistItem): bool;

    public function countForCustomer(Customer $customer): int;

    public function activeVariantIdsForCustomer(Customer $customer): array;
}
