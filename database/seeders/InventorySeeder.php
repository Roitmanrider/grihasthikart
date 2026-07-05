<?php

namespace Database\Seeders;

use App\Domains\Inventory\Services\InventoryService;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $location = StockLocation::query()->where('code', 'MAIN')->first();

        if (! $location) {
            return;
        }

        /** @var InventoryService $inventoryService */
        $inventoryService = app(InventoryService::class);

        ProductVariant::query()
            ->with('product')
            ->whereDoesntHave('inventories', fn ($query) => $query->where('stock_location_id', $location->id))
            ->get()
            ->each(function (ProductVariant $variant) use ($location, $inventoryService) {
                $openingStock = $this->openingStockFor($variant->product?->name ?? '');

                $inventoryService->createInventory($variant, $location, [
                    'quantity_on_hand' => $openingStock,
                    'reserved_quantity' => 0,
                    'damaged_quantity' => 0,
                    'low_stock_threshold' => max(5, $openingStock * 0.15),
                    'reorder_level' => max(10, $openingStock * 0.25),
                    'target_stock_level' => max(50, $openingStock * 1.5),
                    'status' => true,
                ]);
            });
    }

    private function openingStockFor(string $productName): float
    {
        return match ($productName) {
            'Wheat Atta', 'Basmati Rice', 'Sugar', 'Salt' => 150.000,
            'Sunflower Oil', 'Toned Milk' => 90.000,
            'Turmeric Powder', 'Red Chilli Powder', 'Tea' => 60.000,
            'Biscuits' => 120.000,
            default => 50.000,
        };
    }
}
