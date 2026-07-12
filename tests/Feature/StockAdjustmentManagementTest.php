<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAdjustmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private StockLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->location = StockLocation::factory()->create([
            'name' => 'Main Store',
            'code' => 'MAIN',
            'is_default' => true,
            'status' => true,
        ]);
    }

    public function test_guest_blocked(): void
    {
        $this->get(route('admin.stock-adjustments.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.stock-adjustments.create'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_page_loads(): void
    {
        $this->variantWithInventory();

        $this->actingAs($this->admin)
            ->get(route('admin.stock-adjustments.index'))
            ->assertOk()
            ->assertSee('Stock Adjustments');

        $this->actingAs($this->admin)
            ->get(route('admin.stock-adjustments.create'))
            ->assertOk()
            ->assertSee('New Stock Adjustment')
            ->assertSee('Current Stock');
    }

    public function test_increase_stock_works(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 10]);

        $this->actingAs($this->admin)
            ->post(route('admin.stock-adjustments.store'), $this->payload($variant, [
                'adjustment_type' => 'increase',
                'quantity' => 4,
            ]))
            ->assertRedirect();

        $this->assertSame('14.000', $inventory->fresh()->quantity_on_hand);
        $this->assertSame('adjustment_in', InventoryMovement::query()->firstOrFail()->movement_type);
        $this->assertSame('14.000', StockAdjustment::query()->firstOrFail()->after_quantity);
    }

    public function test_decrease_stock_works(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 10]);

        $this->actingAs($this->admin)
            ->post(route('admin.stock-adjustments.store'), $this->payload($variant, [
                'adjustment_type' => 'decrease',
                'quantity' => 3,
                'reason' => 'damage',
            ]))
            ->assertRedirect();

        $this->assertSame('7.000', $inventory->fresh()->quantity_on_hand);
        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame('adjustment_out', $movement->movement_type);
        $this->assertSame(StockAdjustment::class, $movement->reference_type);
    }

    public function test_decrease_below_zero_rejected(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 2]);

        $this->actingAs($this->admin)
            ->post(route('admin.stock-adjustments.store'), $this->payload($variant, [
                'adjustment_type' => 'decrease',
                'quantity' => 3,
            ]))
            ->assertSessionHasErrors('stock_adjustment');

        $this->assertSame('2.000', $inventory->fresh()->quantity_on_hand);
        $this->assertSame(0, StockAdjustment::query()->count());
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_set_stock_creates_correct_difference(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 10]);

        $this->actingAs($this->admin)
            ->post(route('admin.stock-adjustments.store'), $this->payload($variant, [
                'adjustment_type' => 'set',
                'quantity' => 6,
                'reason' => 'physical_count_mismatch',
            ]))
            ->assertRedirect();

        $adjustment = StockAdjustment::query()->firstOrFail();
        $this->assertSame('10.000', $adjustment->before_quantity);
        $this->assertSame('6.000', $adjustment->after_quantity);
        $this->assertSame('4.000', InventoryMovement::query()->firstOrFail()->quantity);
        $this->assertSame('6.000', $inventory->fresh()->quantity_on_hand);
    }

    private function payload(ProductVariant $variant, array $overrides = []): array
    {
        return array_merge([
            'product_variant_id' => $variant->id,
            'adjustment_type' => 'increase',
            'quantity' => 1,
            'reason' => 'manual_correction',
            'notes' => 'Correction',
            'reference_number' => 'ADJ-1',
            'adjustment_date' => now()->toDateString(),
        ], $overrides);
    }

    private function variantWithInventory(array $inventoryOverrides = []): array
    {
        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        $inventory = Inventory::factory()->create(array_merge([
            'product_variant_id' => $variant->id,
            'stock_location_id' => $this->location->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ], $inventoryOverrides));

        return [$variant, $inventory];
    }
}
