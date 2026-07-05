<?php

namespace Tests\Unit;

use App\Domains\Inventory\Repositories\InventoryRepository;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_inventory_by_search_location_status_and_low_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Wheat Atta']);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'GK-ATTA-1KG',
            'variant_name' => '1kg',
        ]);
        $location = StockLocation::factory()->create(['name' => 'Main Store']);
        $matched = Inventory::factory()->lowStock()->create([
            'product_variant_id' => $variant->id,
            'stock_location_id' => $location->id,
            'status' => true,
        ]);

        Inventory::factory()->create(['status' => false]);

        $repository = new InventoryRepository(new Inventory);

        $inventories = $repository->paginatedList([
            'search' => 'ATTA',
            'stock_location_id' => $location->id,
            'status' => 1,
            'low_stock' => 1,
        ]);

        $this->assertTrue($inventories->getCollection()->contains('id', $matched->id));
        $this->assertCount(1, $inventories->getCollection());
    }

    public function test_it_finds_and_locks_inventory_for_variant_location(): void
    {
        $inventory = Inventory::factory()->create();
        $repository = new InventoryRepository(new Inventory);

        $found = $repository->findForVariantLocation($inventory->product_variant_id, $inventory->stock_location_id);
        $locked = $repository->lockForVariantLocation($inventory->product_variant_id, $inventory->stock_location_id);

        $this->assertSame($inventory->id, $found?->id);
        $this->assertSame($inventory->id, $locked?->id);
    }

    public function test_it_bulk_updates_soft_deletes_and_restores_inventory(): void
    {
        $inventories = Inventory::factory()->count(2)->create(['status' => true]);
        $ids = $inventories->pluck('id')->all();

        $repository = new InventoryRepository(new Inventory);

        $this->assertSame(2, $repository->bulkUpdateStatus($ids, false));
        $this->assertSame(0, Inventory::query()->where('status', true)->count());

        $this->assertSame(2, $repository->bulkDelete($ids));
        $this->assertSame(2, Inventory::onlyTrashed()->count());

        $this->assertSame(2, $repository->bulkRestore($ids));
        $this->assertSame(0, Inventory::onlyTrashed()->count());
    }
}
