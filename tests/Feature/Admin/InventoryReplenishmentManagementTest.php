<?php

namespace Tests\Feature\Admin;

use App\Domains\Inventory\Services\InventoryService;
use App\Domains\Inventory\Services\ReplenishmentService;
use App\Models\DailyOffer;
use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryReplenishmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_replenishment_calculation_statuses_and_recommendations(): void
    {
        $inStock = $this->inventory(['quantity_on_hand' => 25, 'reserved_quantity' => 4, 'damaged_quantity' => 1, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $equalReorder = $this->inventory(['quantity_on_hand' => 10, 'reserved_quantity' => 0, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $belowReorder = $this->inventory(['quantity_on_hand' => 8, 'reserved_quantity' => 2, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $zeroStock = $this->inventory(['quantity_on_hand' => 0, 'reserved_quantity' => 0, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $negativeStock = $this->inventory(['quantity_on_hand' => 2, 'reserved_quantity' => 5, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 20]);
        $aboveTarget = $this->inventory(['quantity_on_hand' => 50, 'reserved_quantity' => 0, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $noTarget = $this->inventory(['quantity_on_hand' => 5, 'reserved_quantity' => 0, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => null]);
        $zeroReorder = $this->inventory(['quantity_on_hand' => 1, 'reserved_quantity' => 0, 'damaged_quantity' => 0, 'reorder_level' => 0, 'target_stock_level' => 5]);

        $this->assertSame('IN_STOCK', $inStock->stock_status);
        $this->assertSame(10.0, $inStock->recommended_purchase_quantity);
        $this->assertSame('LOW_STOCK', $equalReorder->stock_status);
        $this->assertSame('LOW_STOCK', $belowReorder->stock_status);
        $this->assertSame(24.0, $belowReorder->recommended_purchase_quantity);
        $this->assertSame('OUT_OF_STOCK', $zeroStock->stock_status);
        $this->assertSame('OUT_OF_STOCK', $negativeStock->stock_status);
        $this->assertSame(23.0, $negativeStock->recommended_purchase_quantity);
        $this->assertSame(0.0, $aboveTarget->recommended_purchase_quantity);
        $this->assertNull($noTarget->recommended_purchase_quantity);
        $this->assertSame('IN_STOCK', $zeroReorder->stock_status);
    }

    public function test_target_stock_lower_than_reorder_is_rejected(): void
    {
        [$variant, $location] = $this->variantAndLocation();

        $this->actingAs($this->admin)->post(route('admin.inventories.store'), [
            'product_variant_id' => $variant->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 10,
            'reorder_level' => 20,
            'target_stock_level' => 10,
            'status' => 1,
        ])->assertSessionHasErrors('target_stock_level');
    }

    public function test_replenishment_page_filters_supplier_and_prefills_purchase(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Fresh Farm Traders']);
        $inventory = $this->inventory(['quantity_on_hand' => 5, 'reserved_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $this->purchaseHistory($inventory->productVariant, $supplier, 42.25);
        $other = $this->inventory(['quantity_on_hand' => 50, 'reserved_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 60]);

        $this->actingAs($this->admin)
            ->get(route('admin.inventory.replenishment.index', ['stock_status' => 'reorder_needed', 'supplier_id' => $supplier->id]))
            ->assertOk()
            ->assertSee('Fresh Farm Traders')
            ->assertSee($inventory->productVariant->sku)
            ->assertDontSee($other->productVariant->sku)
            ->assertSee('25.000');

        $this->actingAs($this->admin)
            ->post(route('admin.inventory.replenishment.purchase', $inventory))
            ->assertRedirect(route('admin.purchases.create'))
            ->assertSessionHasInput('supplier_id', $supplier->id)
            ->assertSessionHasInput('items.0.product_variant_id', $inventory->product_variant_id)
            ->assertSessionHasInput('items.0.quantity', 25.0)
            ->assertSessionHasInput('items.0.purchase_price', 42.25);
    }

    public function test_reservations_daily_offer_and_purchase_posting_affect_replenishment_without_double_counting(): void
    {
        $inventory = $this->inventory(['quantity_on_hand' => 12, 'reserved_quantity' => 5, 'damaged_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        DailyOffer::factory()->create([
            'product_variant_id' => $inventory->product_variant_id,
            'allocated_quantity' => 6,
        ]);

        $this->assertSame(7.0, $inventory->fresh()->available_quantity);
        $this->assertSame('LOW_STOCK', $inventory->fresh()->stock_status);
        $this->assertSame(23.0, $inventory->fresh()->recommended_purchase_quantity);

        app(InventoryService::class)->releaseReservedStock($inventory->product_variant_id, $inventory->stock_location_id, 5, 'Cart expired');
        $this->assertSame('IN_STOCK', $inventory->fresh()->stock_status);

        app(InventoryService::class)->adjustStock($inventory->fresh(), 'purchase', 30, 'Purchase posted');
        $this->assertSame(42.0, $inventory->fresh()->available_quantity);
        $this->assertSame(0.0, $inventory->fresh()->recommended_purchase_quantity);
    }

    public function test_low_stock_notification_is_deduped_until_recovery(): void
    {
        $inventory = $this->inventory(['quantity_on_hand' => 20, 'reserved_quantity' => 0, 'reorder_level' => 10, 'target_stock_level' => 30]);
        $service = app(InventoryService::class);
        $replenishment = app(ReplenishmentService::class);

        $service->adjustStock($inventory, 'adjustment_out', 12, 'Drop below reorder');
        $this->assertSame(1, Notification::query()->where('type', 'inventory.low_stock')->count());

        Notification::query()->firstOrFail()->markAsRead();
        $replenishment->checkTransitions(collect([$inventory->fresh()]));
        $this->assertSame(1, Notification::query()->where('type', 'inventory.low_stock')->count());

        $service->adjustStock($inventory->fresh(), 'purchase', 10, 'Recovered');
        $this->assertSame('IN_STOCK', $inventory->fresh()->low_stock_state);

        $service->adjustStock($inventory->fresh(), 'adjustment_out', 10, 'Low again');
        $this->assertSame(2, Notification::query()->where('type', 'inventory.low_stock')->count());
    }

    public function test_product_import_rejects_target_below_reorder(): void
    {
        StockLocation::factory()->default()->create();

        $csv = UploadedFile::fake()->createWithContent('products.csv', implode("\n", [
            'product_name,brand_name,category,subcategory,sub_subcategory,variant_name,sku,mrp,selling_price,purchase_price,gst_rate,hsn_code,barcode,weight,unit,opening_stock,low_stock_threshold,reorder_level,target_stock_level,is_featured,is_trending,is_popular,is_new_arrival,status,product_image,variant_image,short_description,description,meta_title,meta_description,meta_keywords',
            'Import Rice,Acme,Food,Staples,,1kg,IMP-RICE-1,100,90,70,5,,,1,kg,10,,20,10,0,0,0,0,active,,,,,,',
        ]));

        $this->actingAs($this->admin)
            ->post(route('admin.product-imports.preview'), [
                'csv_file' => $csv,
                'duplicate_action' => 'update_existing',
            ])
            ->assertSessionHas('warning');
    }

    public function test_authorization_and_routes_are_protected(): void
    {
        $inventory = $this->inventory();
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get(route('admin.inventory.replenishment.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.inventory.replenishment.purchase', $inventory))->assertForbidden();

        foreach (['admin.inventory.replenishment.index', 'admin.inventory.replenishment.purchase'] as $routeName) {
            $middleware = Route::getRoutes()->getByName($routeName)->gatherMiddleware();
            $this->assertContains('auth', $middleware);
            $this->assertContains('can:manage-inventory', $middleware);
        }
    }

    private function inventory(array $attributes = []): Inventory
    {
        [$variant, $location] = $this->variantAndLocation();

        return Inventory::factory()->create(array_merge([
            'product_variant_id' => $variant->id,
            'stock_location_id' => $location->id,
            'quantity_on_hand' => 25,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'reorder_level' => 10,
            'target_stock_level' => 30,
            'status' => true,
        ], $attributes));
    }

    private function variantAndLocation(): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        return [$variant, StockLocation::factory()->create(['status' => true])];
    }

    private function purchaseHistory(ProductVariant $variant, Supplier $supplier, float $price): void
    {
        $purchase = PurchaseEntry::query()->create([
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PUR-TEST-'.$variant->id,
            'purchase_date' => now()->toDateString(),
            'subtotal' => $price,
            'gst_total' => 0,
            'discount_total' => 0,
            'cgst_total' => 0,
            'sgst_total' => 0,
            'grand_total' => $price,
            'freight_allocation' => 0,
            'status' => PurchaseEntry::STATUS_POSTED,
        ]);

        $purchase->items()->create([
            'product_variant_id' => $variant->id,
            'sku' => $variant->sku,
            'quantity' => 1,
            'purchase_price' => $price,
            'discount_amount' => 0,
            'gst_rate' => 0,
            'cgst_rate' => 0,
            'sgst_rate' => 0,
            'gst_amount' => 0,
            'cgst_amount' => 0,
            'sgst_amount' => 0,
            'line_total' => $price,
        ]);
    }
}
