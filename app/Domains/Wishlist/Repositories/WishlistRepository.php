<?php

namespace App\Domains\Wishlist\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Wishlist\Contracts\WishlistRepositoryInterface;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WishlistRepository extends BaseRepository implements WishlistRepositoryInterface
{
    public function __construct(WishlistItem $model)
    {
        parent::__construct($model);
    }

    public function forCustomer(Customer $customer, int $perPage = 12): LengthAwarePaginator
    {
        return $this->model
            ->newQuery()
            ->where('customer_id', $customer->id)
            ->with([
                'product.brand',
                'product.primaryImage',
                'productVariant.product.primaryImage',
                'productVariant.primaryImage',
                'productVariant.attributeValues.attribute',
            ])
            ->latest()
            ->paginate($perPage);
    }

    public function findForCustomer(Customer $customer, int $wishlistItemId): WishlistItem
    {
        return $this->model
            ->newQuery()
            ->where('customer_id', $customer->id)
            ->with([
                'product',
                'productVariant.product',
            ])
            ->findOrFail($wishlistItemId);
    }

    public function findExistingForCustomer(Customer $customer, int $productVariantId): ?WishlistItem
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->where('customer_id', $customer->id)
            ->where('product_variant_id', $productVariantId)
            ->first();
    }

    public function createForCustomer(Customer $customer, ProductVariant $variant): WishlistItem
    {
        return $this->model->newQuery()->create([
            'customer_id' => $customer->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function removeForCustomer(Customer $customer, WishlistItem $wishlistItem): bool
    {
        $wishlistItem = $this->findForCustomer($customer, $wishlistItem->id);

        return (bool) $wishlistItem->delete();
    }

    public function countForCustomer(Customer $customer): int
    {
        return $this->model
            ->newQuery()
            ->where('customer_id', $customer->id)
            ->count();
    }
}
