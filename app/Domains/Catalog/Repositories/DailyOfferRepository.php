<?php

namespace App\Domains\Catalog\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Catalog\Contracts\DailyOfferRepositoryInterface;
use App\Models\DailyOffer;
use App\Models\ProductVariant;

class DailyOfferRepository extends BaseRepository implements DailyOfferRepositoryInterface
{
    public function __construct(DailyOffer $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        $query = $this->model->newQuery()
            ->with(['productVariant.product', 'productVariant.primaryImage', 'productVariant.product.primaryImage']);

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(fn ($query) => $query
                ->where('title', 'like', '%'.$search.'%')
                ->orWhere('badge_text', 'like', '%'.$search.'%')
                ->orWhereHas('productVariant', fn ($query) => $query
                    ->where('sku', 'like', '%'.$search.'%')
                    ->orWhere('variant_name', 'like', '%'.$search.'%')
                    ->orWhereHas('product', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))));
        }

        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $query->where('is_active', (bool) $filters['status']);
        }

        if (($filters['current'] ?? null) === '1') {
            $query->current();
        }

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        return $query
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function currentOffers(int $limit = 8)
    {
        return $this->model->newQuery()
            ->current()
            ->whereHas('productVariant', fn ($query) => $query->active()->whereHas('product', fn ($query) => $query->active()))
            ->with(['productVariant.product.brand', 'productVariant.primaryImage', 'productVariant.product.primaryImage'])
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function activeOfferExistsForVariant(int $productVariantId, ?int $ignoreId = null): bool
    {
        $query = $this->model->newQuery()
            ->where('product_variant_id', $productVariantId)
            ->where('is_active', true);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function productVariantOptions()
    {
        return ProductVariant::query()
            ->active()
            ->whereHas('product', fn ($query) => $query->active())
            ->with('product')
            ->orderBy(ProductVariant::query()->getModel()->getTable().'.sku')
            ->get();
    }

    public function findWithTrashed(int $id): DailyOffer
    {
        return $this->model->withTrashed()->findOrFail($id);
    }
}
