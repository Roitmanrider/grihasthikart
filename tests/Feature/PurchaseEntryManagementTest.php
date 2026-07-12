<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\PurchaseEntryItem;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PurchaseEntryManagementTest extends TestCase
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

    public function test_guest_blocked_from_purchases(): void
    {
        $this->get(route('admin.purchases.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.purchases.create'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_purchase_index_and_create_load(): void
    {
        $this->variantWithInventory();

        $this->actingAs($this->admin)
            ->get(route('admin.purchases.index'))
            ->assertOk()
            ->assertSee('Purchases');

        $this->actingAs($this->admin)
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertSee('New Purchase')
            ->assertSee('Stock:');
    }

    public function test_valid_purchase_creates_purchase_items_inventory_and_stock_movement(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 5]);

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $this->payload($variant))
            ->assertRedirect();

        $purchase = PurchaseEntry::query()->firstOrFail();

        $item = PurchaseEntryItem::query()->firstOrFail();

        $this->assertSame($purchase->id, $item->purchase_entry_id);
        $this->assertSame($variant->id, $item->product_variant_id);
        $this->assertSame($variant->sku, $item->sku);
        $this->assertSame('3.000', $item->quantity);
        $this->assertSame('100.00', $item->purchase_price);
        $this->assertSame('5.00', $item->gst_rate);
        $this->assertSame('15.00', $item->gst_amount);
        $this->assertSame('315.00', $item->line_total);
        $this->assertSame('8.000', $inventory->fresh()->quantity_on_hand);

        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame($inventory->id, $movement->inventory_id);
        $this->assertSame($variant->id, $movement->product_variant_id);
        $this->assertSame($this->location->id, $movement->stock_location_id);
        $this->assertSame('purchase', $movement->movement_type);
        $this->assertSame('3.000', $movement->quantity);
        $this->assertSame(PurchaseEntry::class, $movement->reference_type);
        $this->assertSame($purchase->id, $movement->reference_id);
    }

    public function test_purchase_creates_inventory_record_when_variant_has_none(): void
    {
        $variant = $this->variant();

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $this->payload($variant))
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $variant->id,
            'stock_location_id' => $this->location->id,
        ]);
        $this->assertSame('3.000', Inventory::query()->where('product_variant_id', $variant->id)->firstOrFail()->quantity_on_hand);
        $this->assertSame(1, InventoryMovement::query()->where('movement_type', 'purchase')->count());
    }

    public function test_invalid_row_rolls_back_all(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 5]);

        $payload = $this->payload($variant);
        $payload['items'][0]['quantity'] = 0;

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $payload)
            ->assertSessionHasErrors();

        $this->assertSame(0, PurchaseEntry::query()->count());
        $this->assertSame(0, PurchaseEntryItem::query()->count());
        $this->assertSame('5.000', $inventory->fresh()->quantity_on_hand);
    }

    public function test_duplicate_variant_rejected(): void
    {
        [$variant] = $this->variantWithInventory();

        $payload = $this->payload($variant);
        $payload['items'][] = $payload['items'][0];

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $payload)
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseEntry::query()->count());
    }

    public function test_totals_are_calculated_server_side(): void
    {
        [$variant] = $this->variantWithInventory();

        $payload = $this->payload($variant);
        $payload['subtotal'] = 1;
        $payload['grand_total'] = 1;

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $payload)
            ->assertRedirect();

        $purchase = PurchaseEntry::query()->firstOrFail();

        $this->assertSame('300.00', $purchase->subtotal);
        $this->assertSame('15.00', $purchase->gst_total);
        $this->assertSame('315.00', $purchase->grand_total);
    }

    public function test_purchase_item_discount_and_cgst_sgst_are_calculated_server_side(): void
    {
        [$variant] = $this->variantWithInventory();

        $payload = $this->payload($variant);
        $payload['freight_allocation'] = 75;
        $payload['items'][0]['discount_amount'] = 30;
        $payload['items'][0]['gst_rate'] = 12;

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $payload)
            ->assertRedirect();

        $purchase = PurchaseEntry::query()->firstOrFail();
        $item = PurchaseEntryItem::query()->firstOrFail();

        $this->assertSame('300.00', $purchase->subtotal);
        $this->assertSame('30.00', $purchase->discount_total);
        $this->assertSame('16.20', $purchase->cgst_total);
        $this->assertSame('16.20', $purchase->sgst_total);
        $this->assertSame('32.40', $purchase->gst_total);
        $this->assertSame('302.40', $purchase->grand_total);
        $this->assertSame('75.00', $purchase->freight_allocation);
        $this->assertSame('30.00', $item->discount_amount);
        $this->assertSame('6.00', $item->cgst_rate);
        $this->assertSame('6.00', $item->sgst_rate);
        $this->assertSame('16.20', $item->cgst_amount);
        $this->assertSame('16.20', $item->sgst_amount);
    }

    public function test_purchase_create_shows_confirmation_modal(): void
    {
        $this->variantWithInventory();

        $this->actingAs($this->admin)
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertSee('Confirm Posted Purchase')
            ->assertSee('Freight allocation is audit-only');
    }

    public function test_can_submit_more_than_eight_purchase_rows(): void
    {
        $items = [];

        for ($index = 0; $index < 9; $index++) {
            [$variant] = $this->variantWithInventory();
            $items[] = [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'purchase_price' => 10,
                'discount_amount' => 0,
                'gst_rate' => 0,
            ];
        }

        $payload = $this->payload($items[0]['product_variant_id'] ? ProductVariant::query()->findOrFail($items[0]['product_variant_id']) : $this->variant());
        $payload['items'] = $items;

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.store'), $payload)
            ->assertRedirect();

        $this->assertSame(9, PurchaseEntryItem::query()->count());
    }

    public function test_purchase_csv_template_downloads_with_gst_split_columns(): void
    {
        $this->variantWithInventory();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.purchases.template'))
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString(
            'product_name,variant_name,sku,current_stock,quantity,purchase_price,discount,gst_rate,cgst_rate,sgst_rate,batch_number,expiry_date',
            $csv
        );
    }

    public function test_purchase_csv_import_ignores_blank_quantity_rows_and_updates_inventory(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 5]);
        $supplier = Supplier::factory()->create();
        $csv = implode("\n", [
            'product_name,variant_name,sku,current_stock,quantity,purchase_price,discount,gst_rate,cgst_rate,sgst_rate,batch_number,expiry_date',
            'Wheat,1kg,'.$variant->sku.',5,2,100,10,12,,,B-CSV,'.now()->addYear()->toDateString(),
            'Blank,1kg,NO-SKU,0,,,,,,,,',
        ]);

        $preview = $this->actingAs($this->admin)
            ->post(route('admin.purchases.preview'), [
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'freight_allocation' => 50,
                'csv_file' => UploadedFile::fake()->createWithContent('purchase.csv', $csv),
            ])
            ->assertOk()
            ->assertSee($variant->sku)
            ->assertDontSee('NO-SKU');

        $preview->assertSee('Rs. 212.80');

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.import'), [
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'freight_allocation' => 50,
                'items' => [
                    [
                        'product_variant_id' => $variant->id,
                        'quantity' => 2,
                        'purchase_price' => 100,
                        'discount_amount' => 10,
                        'gst_rate' => 12,
                        'batch_number' => 'B-CSV',
                        'expiry_date' => now()->addYear()->toDateString(),
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('7.000', $inventory->fresh()->quantity_on_hand);
        $this->assertSame(1, PurchaseEntryItem::query()->count());
        $this->assertSame('50.00', PurchaseEntry::query()->firstOrFail()->freight_allocation);
    }

    public function test_purchase_csv_preview_rejects_duplicate_skus_clearly(): void
    {
        [$variant] = $this->variantWithInventory();
        $csv = implode("\n", [
            'product_name,variant_name,sku,current_stock,quantity,purchase_price,discount,gst_rate,cgst_rate,sgst_rate,batch_number,expiry_date',
            'Wheat,1kg,'.$variant->sku.',5,1,100,0,5,,,B-1,'.now()->addYear()->toDateString(),
            'Wheat,1kg,'.$variant->sku.',5,2,100,0,5,,,B-2,'.now()->addYear()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.preview'), [
                'purchase_date' => now()->toDateString(),
                'csv_file' => UploadedFile::fake()->createWithContent('purchase.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('Duplicate SKU '.$variant->sku.' is not allowed');
    }

    public function test_purchase_csv_preview_rejects_invalid_expiry_date(): void
    {
        [$variant] = $this->variantWithInventory();
        $csv = implode("\n", [
            'product_name,variant_name,sku,current_stock,quantity,purchase_price,discount,gst_rate,cgst_rate,sgst_rate,batch_number,expiry_date',
            'Wheat,1kg,'.$variant->sku.',5,1,100,0,5,,,B-1,31-07-2026',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.preview'), [
                'purchase_date' => now()->toDateString(),
                'csv_file' => UploadedFile::fake()->createWithContent('purchase.csv', $csv),
            ])
            ->assertOk()
            ->assertSee('Expiry date must use YYYY-MM-DD format.');
    }

    public function test_purchase_csv_import_rejects_duplicate_variants_before_creating_purchase(): void
    {
        [$variant, $inventory] = $this->variantWithInventory(['quantity_on_hand' => 5]);

        $this->actingAs($this->admin)
            ->post(route('admin.purchases.import'), [
                'purchase_date' => now()->toDateString(),
                'items' => [
                    [
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'purchase_price' => 100,
                        'discount_amount' => 0,
                        'gst_rate' => 5,
                    ],
                    [
                        'product_variant_id' => $variant->id,
                        'quantity' => 1,
                        'purchase_price' => 100,
                        'discount_amount' => 0,
                        'gst_rate' => 5,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseEntry::query()->count());
        $this->assertSame('5.000', $inventory->fresh()->quantity_on_hand);
    }

    public function test_purchase_print_page_loads(): void
    {
        [$variant] = $this->variantWithInventory();
        $this->actingAs($this->admin)->post(route('admin.purchases.store'), $this->payload($variant));
        $purchase = PurchaseEntry::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.purchases.print', $purchase))
            ->assertOk()
            ->assertSee($purchase->purchase_number);
    }

    private function payload(ProductVariant $variant): array
    {
        return [
            'purchase_date' => now()->toDateString(),
            'bill_number' => 'BILL-100',
            'freight_allocation' => 0,
            'notes' => 'Opening supplier bill',
            'items' => [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 3,
                    'purchase_price' => 100,
                    'discount_amount' => 0,
                    'gst_rate' => 5,
                    'batch_number' => 'B-1',
                    'expiry_date' => now()->addYear()->toDateString(),
                ],
            ],
        ];
    }

    private function variantWithInventory(array $inventoryOverrides = []): array
    {
        $variant = $this->variant();
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

    private function variant(): ProductVariant
    {
        $product = Product::factory()->create(['status' => true, 'name' => 'Wheat Atta']);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'sku' => fake()->unique()->bothify('GK-ATTA-####'),
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);

        return $variant;
    }
}
