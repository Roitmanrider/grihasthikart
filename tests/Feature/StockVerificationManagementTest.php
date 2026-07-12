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

class StockVerificationManagementTest extends TestCase
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
        $this->get(route('admin.stock-verifications.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.stock-verifications.create'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_page_loads(): void
    {
        $this->variantWithInventory();

        $this->actingAs($this->admin)
            ->get(route('admin.stock-verifications.index'))
            ->assertOk()
            ->assertSee('Stock Verification');

        $this->actingAs($this->admin)
            ->get(route('admin.stock-verifications.create'))
            ->assertOk()
            ->assertSee('Record Stock Verification')
            ->assertSee('Current Stock');
    }

    public function test_stock_verification_matched_works(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 8]);

        $this->actingAs($this->admin)
            ->post(route('admin.stock-verifications.store'), $this->payload($variant, ['counted_stock' => 8]))
            ->assertRedirect(route('admin.stock-verifications.index'));

        $adjustment = StockAdjustment::query()->firstOrFail();
        $this->assertSame('8.000', $adjustment->before_quantity);
        $this->assertSame('8.000', $adjustment->after_quantity);
        $this->assertSame('0.000', $adjustment->quantity);
        $this->assertSame('8.000', $inventory->fresh()->quantity_on_hand);
        $this->assertSame(0, InventoryMovement::query()->count());
    }

    public function test_stock_verification_mismatch_adjusts_stock_and_creates_movement(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 8]);

        $this->actingAs($this->admin)
            ->post(route('admin.stock-verifications.store'), $this->payload($variant, ['counted_stock' => 5]))
            ->assertRedirect(route('admin.stock-verifications.index'));

        $adjustment = StockAdjustment::query()->firstOrFail();
        $this->assertSame('8.000', $adjustment->before_quantity);
        $this->assertSame('5.000', $adjustment->after_quantity);
        $this->assertSame('3.000', $adjustment->quantity);
        $this->assertSame('5.000', $inventory->fresh()->quantity_on_hand);

        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame('adjustment_out', $movement->movement_type);
        $this->assertSame('3.000', $movement->quantity);
        $this->assertSame(StockAdjustment::class, $movement->reference_type);
        $this->assertSame($adjustment->id, $movement->reference_id);
    }

    private function payload(ProductVariant $variant, array $overrides = []): array
    {
        return array_merge([
            'product_variant_id' => $variant->id,
            'counted_stock' => 8,
            'notes' => 'Shelf count',
            'reference_number' => 'COUNT-1',
            'verification_date' => now()->toDateString(),
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
            'quantity_on_hand' => 8,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ], $inventoryOverrides));

        return [$variant, $inventory];
    }
}
