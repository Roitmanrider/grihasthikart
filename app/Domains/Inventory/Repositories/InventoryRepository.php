<?php

namespace App\Domains\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Domains\Inventory\Contracts\InventoryRepositoryInterface;
use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockLocation;

class InventoryRepository extends BaseRepository implements InventoryRepositoryInterface
{
    public function __construct(Inventory $model)
    {
        parent::__construct($model);
    }

    public function paginatedList(array $filters = [], int $perPage = 20)
    {
        return $this->baseQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithRelations(int $id): Inventory
    {
        return $this->model
            ->newQuery()
            ->withTrashed()
            ->with(['productVariant.product.brand', 'stockLocation', 'movements.creator'])
            ->findOrFail($id);
    }

    public function findForVariantLocation(int $productVariantId, int $stockLocationId): ?Inventory
    {
        return $this->model
            ->newQuery()
            ->where('product_variant_id', $productVariantId)
            ->where('stock_location_id', $stockLocationId)
            ->first();
    }

    public function lockForVariantLocation(int $productVariantId, int $stockLocationId): ?Inventory
    {
        return $this->model
            ->newQuery()
            ->where('product_variant_id', $productVariantId)
            ->where('stock_location_id', $stockLocationId)
            ->lockForUpdate()
            ->first();
    }

    public function movementHistory(Inventory $inventory, int $perPage = 20)
    {
        return $inventory->movements()
            ->with('creator')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function activeLocations()
    {
        return StockLocation::query()
            ->active()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function allLocations()
    {
        return StockLocation::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    public function activeVariants()
    {
        return ProductVariant::query()
            ->active()
            ->with('product')
            ->whereHas('product', fn ($query) => $query->active())
            ->orderBy('sku')
            ->get();
    }

    public function bulkUpdateStatus(array $ids, bool $status): int
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->model
            ->newQuery()
            ->whereIn('id', $ids)
            ->delete();
    }

    public function bulkRestore(array $ids): int
    {
        return $this->model
            ->onlyTrashed()
            ->whereIn('id', $ids)
            ->restore();
    }

    private function baseQuery(array $filters)
    {
        $query = $this->model
            ->newQuery()
            ->with(['productVariant.product.brand', 'stockLocation'])
            ->whereHas('productVariant');

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function ($query) use ($search) {
                $query->whereHas('productVariant', fn ($query) => $query->search($search))
                    ->orWhereHas('productVariant.product', fn ($query) => $query->where('name', 'like', '%'.$search.'%'));
            });
        }

        if (($filters['trashed'] ?? null) === 'only') {
            $query->onlyTrashed();
        } elseif (($filters['trashed'] ?? null) === 'with') {
            $query->withTrashed();
        }

        foreach (['product_variant_id', 'stock_location_id'] as $filter) {
            if (($filters[$filter] ?? null) !== null && $filters[$filter] !== '') {
                $query->where($filter, (int) $filters[$filter]);
            }
        }

        if (($filters['status'] ?? null) !== null && $filters['status'] !== '') {
            $query->where('status', (bool) $filters['status']);
        }

        if (($filters['low_stock'] ?? null) === '1') {
            $query->whereNotNull('low_stock_threshold')
                ->whereRaw('(quantity_on_hand - reserved_quantity - damaged_quantity) <= low_stock_threshold');
        }

        $sort = $filters['sort'] ?? 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, ['quantity_on_hand', 'reserved_quantity', 'damaged_quantity', 'low_stock_threshold', 'created_at', 'status'], true)) {
            $sort = 'created_at';
        }

        return $query->orderBy($sort, $direction);
    }
}
