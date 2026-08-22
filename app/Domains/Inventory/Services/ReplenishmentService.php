<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Notification\Services\NotificationService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\PurchaseEntryItem;
use App\Models\StockLocation;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class ReplenishmentService
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->baseQuery($filters);
        $this->applySorting($query, $filters['sort'] ?? 'critical');

        $paginator = $query->paginate($perPage)->withQueryString();
        $this->attachLastPurchaseData($paginator->getCollection());

        return $paginator;
    }

    public function dashboardItems(int $limit = 5): Collection
    {
        $items = $this->baseQuery(['stock_status' => 'reorder_needed'])
            ->orderByRaw($this->criticalityOrderSql())
            ->limit($limit)
            ->get();

        $this->attachLastPurchaseData($items);

        return $items;
    }

    public function summary(array $filters = []): array
    {
        $base = $this->baseQuery($filters);

        return [
            'in_stock' => (clone $base)->whereRaw($this->availableSql().' > COALESCE(reorder_level, -999999999)')->count(),
            'low_stock' => (clone $base)->whereNotNull('reorder_level')->whereRaw($this->availableSql().' > 0')->whereRaw($this->availableSql().' <= reorder_level')->count(),
            'out_of_stock' => (clone $base)->whereRaw($this->availableSql().' <= 0')->count(),
            'reorder_needed' => (clone $base)->whereNotNull('reorder_level')->whereRaw($this->availableSql().' <= reorder_level')->count(),
            'no_target_configured' => (clone $base)->whereNull('target_stock_level')->count(),
            'no_supplier_assigned' => (clone $base)->whereDoesntHave('productVariant.purchaseEntryItems.purchaseEntry')->count(),
        ];
    }

    public function options(): array
    {
        return [
            'suppliers' => Schema::hasTable('suppliers') ? Supplier::query()->active()->orderBy('name')->get() : collect(),
            'locations' => StockLocation::query()->active()->orderBy('display_order')->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'categories' => Category::query()->orderBy('name')->get(),
        ];
    }

    public function checkTransitions(?Collection $inventories = null): int
    {
        $inventories ??= Inventory::query()
            ->active()
            ->with(['productVariant.product', 'stockLocation'])
            ->whereHas('productVariant')
            ->get();

        $created = 0;

        foreach ($inventories as $inventory) {
            $fresh = $inventory->fresh(['productVariant.product', 'stockLocation']);
            $oldState = $fresh->low_stock_state ?: 'IN_STOCK';
            $newState = $fresh->stock_status;

            if ($newState === 'IN_STOCK') {
                if ($oldState !== 'IN_STOCK') {
                    $fresh->forceFill([
                        'low_stock_state' => 'IN_STOCK',
                        'low_stock_notified_at' => null,
                    ])->save();
                }

                continue;
            }

            if ($oldState !== $newState) {
                $this->notificationService->notifyAdminReplenishmentStockState($fresh, $newState);
                $fresh->forceFill([
                    'low_stock_state' => $newState,
                    'low_stock_notified_at' => now(),
                ])->save();
                $created++;
            }
        }

        return $created;
    }

    public function prefillForInventory(Inventory $inventory): array
    {
        $inventory->loadMissing(['productVariant.product', 'productVariant.purchaseEntryItems.purchaseEntry.supplier']);
        $this->attachLastPurchaseData(collect([$inventory]));
        $lastItem = $inventory->last_purchase_item;

        return [
            'stock_location_id' => $inventory->stock_location_id,
            'supplier_id' => $inventory->last_supplier?->id,
            'items' => [[
                'product_variant_id' => $inventory->product_variant_id,
                'quantity' => $inventory->recommended_purchase_quantity ?: '',
                'purchase_price' => $lastItem ? (float) $lastItem->purchase_price : '',
                'discount_amount' => 0,
                'gst_rate' => $inventory->productVariant?->product?->gst_rate ?? 0,
                'cgst_rate' => '',
                'sgst_rate' => '',
                'batch_number' => '',
                'expiry_date' => '',
            ]],
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        $query = Inventory::query()
            ->active()
            ->with(['productVariant.product.brand', 'productVariant.product.categories', 'stockLocation'])
            ->whereHas('productVariant');

        if (($filters['search'] ?? null) !== null && $filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(function (Builder $query) use ($search) {
                $query->whereHas('productVariant', fn (Builder $variantQuery) => $variantQuery->search($search))
                    ->orWhereHas('productVariant.product', fn (Builder $productQuery) => $productQuery->where('name', 'like', '%'.$search.'%'));
            });
        }

        if (($filters['stock_location_id'] ?? null) !== null && $filters['stock_location_id'] !== '') {
            $query->where('stock_location_id', (int) $filters['stock_location_id']);
        }

        if (($filters['brand_id'] ?? null) !== null && $filters['brand_id'] !== '') {
            $query->whereHas('productVariant.product', fn (Builder $productQuery) => $productQuery->where('brand_id', (int) $filters['brand_id']));
        }

        if (($filters['category_id'] ?? null) !== null && $filters['category_id'] !== '') {
            $query->whereHas('productVariant.product.categories', fn (Builder $categoryQuery) => $categoryQuery->where('categories.id', (int) $filters['category_id']));
        }

        if (($filters['supplier_id'] ?? null) !== null && $filters['supplier_id'] !== '') {
            if ((string) $filters['supplier_id'] === 'none') {
                $query->whereDoesntHave('productVariant.purchaseEntryItems.purchaseEntry');
            } else {
                $query->whereHas('productVariant.purchaseEntryItems.purchaseEntry', fn (Builder $purchaseQuery) => $purchaseQuery->where('supplier_id', (int) $filters['supplier_id']));
            }
        }

        match ($filters['stock_status'] ?? null) {
            'low' => $query->whereNotNull('reorder_level')->whereRaw($this->availableSql().' > 0')->whereRaw($this->availableSql().' <= reorder_level'),
            'out' => $query->whereRaw($this->availableSql().' <= 0'),
            'reorder_needed' => $query->whereNotNull('reorder_level')->whereRaw($this->availableSql().' <= reorder_level'),
            'no_target' => $query->whereNull('target_stock_level'),
            default => null,
        };

        return $query;
    }

    private function applySorting(Builder $query, string $sort): void
    {
        match ($sort) {
            'lowest_sellable' => $query->orderByRaw($this->availableSql().' asc'),
            'highest_recommended' => $query->orderByRaw('CASE WHEN target_stock_level IS NULL THEN -999999999 ELSE target_stock_level - ('.$this->availableSql().') END desc'),
            'product_name' => $query
                ->join('product_variants as sort_variants', 'sort_variants.id', '=', 'inventories.product_variant_id')
                ->join('products as sort_products', 'sort_products.id', '=', 'sort_variants.product_id')
                ->orderBy('sort_products.name')
                ->select('inventories.*'),
            'sku' => $query
                ->join('product_variants as sort_sku_variants', 'sort_sku_variants.id', '=', 'inventories.product_variant_id')
                ->orderBy('sort_sku_variants.sku')
                ->select('inventories.*'),
            default => $query->orderByRaw($this->criticalityOrderSql()),
        };
    }

    private function attachLastPurchaseData(Collection $inventories): void
    {
        $variantIds = $inventories->pluck('product_variant_id')->filter()->unique()->values();

        if ($variantIds->isEmpty()) {
            return;
        }

        $lastItems = PurchaseEntryItem::query()
            ->with('purchaseEntry.supplier')
            ->whereIn('product_variant_id', $variantIds)
            ->whereHas('purchaseEntry')
            ->latest('id')
            ->get()
            ->unique('product_variant_id')
            ->keyBy('product_variant_id');

        $inventories->each(function (Inventory $inventory) use ($lastItems): void {
            $lastItem = $lastItems->get($inventory->product_variant_id);
            $inventory->setRelation('last_purchase_item', $lastItem);
            $inventory->setRelation('last_supplier', $lastItem?->purchaseEntry?->supplier);
        });
    }

    private function availableSql(): string
    {
        return '(quantity_on_hand - reserved_quantity - damaged_quantity)';
    }

    private function criticalityOrderSql(): string
    {
        $available = $this->availableSql();

        return "CASE WHEN {$available} <= 0 THEN 0 WHEN reorder_level IS NOT NULL AND {$available} <= reorder_level THEN 1 ELSE 2 END asc, CASE WHEN reorder_level IS NULL OR reorder_level = 0 THEN {$available} ELSE {$available} / reorder_level END asc, {$available} asc";
    }
}
