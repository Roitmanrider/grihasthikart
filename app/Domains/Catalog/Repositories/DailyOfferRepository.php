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
            ->with(['productVariant.product.categories.parent', 'productVariant.primaryImage', 'productVariant.product.primaryImage']);

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

        if (($filters['current'] ?? null) !== null && $filters['current'] !== '') {
            $this->applyLifecycleFilter($query, $filters['current']);
        }

        if (($filters['date'] ?? null) !== null && $filters['date'] !== '') {
            $query->whereDate('starts_at', '<=', $filters['date'])
                ->whereDate('ends_at', '>=', $filters['date']);
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
            ->whereHas('productVariant.inventories', fn ($query) => $query
                ->active()
                ->whereRaw('(quantity_on_hand - reserved_quantity - damaged_quantity) > 0'))
            ->with(['productVariant.inventories', 'productVariant.product.brand', 'productVariant.product.categories.parent', 'productVariant.primaryImage', 'productVariant.product.primaryImage'])
            ->orderBy('display_order')
            ->orderByDesc('created_at')
            ->take($limit)
            ->get();
    }

    public function activeOfferExistsForVariant(int $productVariantId, ?int $ignoreId = null, mixed $startsAt = null, mixed $endsAt = null): bool
    {
        $query = $this->model->newQuery()
            ->where('product_variant_id', $productVariantId)
            ->where('is_active', true);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($startsAt || $endsAt) {
            $query->where(function ($query) use ($startsAt, $endsAt) {
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

    private function applyLifecycleFilter($query, string $lifecycle): void
    {
        $now = now(config('app.timezone'));

        match ($lifecycle) {
            'scheduled' => $query->where('is_active', true)->where('starts_at', '>', $now),
            'active' => $query->current(),
            'expired' => $query->where('is_active', true)->whereNotNull('ends_at')->where('ends_at', '<', $now),
            'inactive' => $query->where('is_active', false),
            default => null,
        };
    }
}
