<?php

namespace Tests\Feature\Admin;

use App\Domains\Inventory\Services\InventoryService;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_create_inventory_for_variant_location(): void
    {
        [$variant, $location] = $this->variantAndLocation();

        $response = $this->actingAs($this->admin)->post(route('admin.inventories.store'), [
            'product_variant_id' => $variant->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 100,
            'reserved_quantity' => 5,
            'damaged_quantity' => 2,
            'low_stock_threshold' => 10,
            'reorder_level' => 20,
            'target_stock_level' => 150,
            'status' => 1,
        ]);

        $response->assertRedirect(route('admin.inventories.index'));

        $inventory = Inventory::query()->where('product_variant_id', $variant->id)->firstOrFail();

        $this->assertSame($location->id, $inventory->stock_location_id);
        $this->assertSame(93.0, $inventory->available_quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'movement_type' => 'opening',
            'balance_after' => 100,
        ]);
    }

    public function test_unique_variant_location_inventory_row_is_enforced(): void
    {
        [$variant, $location] = $this->variantAndLocation();
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'stock_location_id' => $location->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventories.store'), [
            'product_variant_id' => $variant->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 10,
        ])->assertSessionHasErrors('product_variant_id');
    }

    public function test_adjustment_creates_movement_log_and_blocks_negative_stock(): void
    {
        $inventory = Inventory::factory()->create([
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventories.adjust.store', $inventory), [
            'movement_type' => 'adjustment_out',
            'quantity' => 3,
            'note' => 'Stock correction',
        ])->assertRedirect(route('admin.inventories.show', $inventory));

        $this->assertSame('7.000', $inventory->fresh()->quantity_on_hand);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_id' => $inventory->id,
            'movement_type' => 'adjustment_out',
            'quantity' => 3,
            'balance_after' => 7,
            'note' => 'Stock correction',
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventories.adjust.store', $inventory), [
            'movement_type' => 'adjustment_out',
            'quantity' => 20,
        ])->assertSessionHasErrors('inventory');

        $this->assertSame('7.000', $inventory->fresh()->quantity_on_hand);
    }

    public function test_reserve_release_and_damaged_stock_affect_available_quantity(): void
    {
        $inventory = Inventory::factory()->create([
            'quantity_on_hand' => 20,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
        ]);

        $service = app(InventoryService::class);

        $service->reserveStock($inventory->product_variant_id, $inventory->stock_location_id, 4, 'Future cart reservation');
        $this->assertSame(16.0, $inventory->fresh()->available_quantity);

        $service->releaseReservedStock($inventory->product_variant_id, $inventory->stock_location_id, 2, 'Reservation expired');
        $this->assertSame(18.0, $inventory->fresh()->available_quantity);

        $service->adjustStock($inventory->fresh(), 'damaged', 3, 'Damaged pouch');
        $this->assertSame(15.0, $inventory->fresh()->available_quantity);

        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'reservation']);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'reservation_release']);
        $this->assertDatabaseHas('inventory_movements', ['movement_type' => 'damaged']);
    }

    public function test_low_stock_indicator_and_movement_history_are_visible(): void
    {
        $inventory = Inventory::factory()->lowStock()->create();
        InventoryMovement::factory()->create([
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'stock_location_id' => $inventory->stock_location_id,
            'movement_type' => 'opening',
            'note' => 'Opening stock',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.inventories.show', $inventory));

        $response->assertOk();
        $response->assertSee('Low Stock');
        $response->assertSee('Movement History');
        $response->assertSee('Opening stock');
    }

    public function test_inactive_stock_location_cannot_be_used_for_adjustment(): void
    {
        $location = StockLocation::factory()->inactive()->create();
        $inventory = Inventory::factory()->create([
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 10,
        ]);

        $this->actingAs($this->admin)->post(route('admin.inventories.adjust.store', $inventory), [
            'movement_type' => 'adjustment_in',
            'quantity' => 1,
        ])->assertSessionHasErrors('inventory');
    }

    public function test_inventory_belongs_to_product_variant_and_variant_show_displays_summary(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
        $inventory = Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 25,
            'reserved_quantity' => 5,
            'damaged_quantity' => 0,
        ]);

        $this->assertSame($variant->id, $inventory->productVariant->id);

        $this->actingAs($this->admin)
            ->get(route('admin.products.variants.show', [$product, $variant]))
            ->assertOk()
            ->assertSee('Inventory Summary')
            ->assertSee('Available: 20.000');
    }

    public function test_authorization_and_routes_are_protected(): void
    {
        $inventory = Inventory::factory()->create();
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get(route('admin.inventories.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.inventories.store'), [])->assertForbidden();
        $this->actingAs($user)->post(route('admin.inventories.adjust.store', $inventory), [])->assertForbidden();

        foreach ([
            'admin.inventories.index',
            'admin.inventories.create',
            'admin.inventories.store',
            'admin.inventories.show',
            'admin.inventories.edit',
            'admin.inventories.update',
            'admin.inventories.destroy',
            'admin.inventories.restore',
            'admin.inventories.adjust',
            'admin.inventories.adjust.store',
            'admin.inventories.bulk-action',
        ] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();

            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-inventory', $middleware);
        }
    }

    public function test_inventory_boundaries_and_no_transactional_modules_created(): void
    {
        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column), $column.' should not exist on products.');
            $this->assertFalse(Schema::hasColumn('product_variants', $column), $column.' should not exist on product variants.');
        }

        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();

        $this->assertNotContains('checkout', $uris);
        $this->assertNotContains('orders', $uris);
        $this->assertNotContains('payment', $uris);
    }

    private function variantAndLocation(): array
    {
        return [
            ProductVariant::factory()->create(),
            StockLocation::factory()->create(['status' => true]),
        ];
    }
}
