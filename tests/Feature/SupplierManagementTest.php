<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseEntry;
use App\Models\StockLocation;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_guest_blocked_from_suppliers(): void
    {
        $supplier = Supplier::factory()->create();

        $this->get(route('admin.suppliers.index'))->assertRedirect(route('login'));
        $this->get(route('admin.suppliers.create'))->assertRedirect(route('login'));
        $this->get(route('admin.suppliers.edit', $supplier))->assertRedirect(route('login'));
        $this->get(route('admin.suppliers.show', $supplier))->assertRedirect(route('login'));
    }

    public function test_admin_supplier_index_create_edit_and_show_load(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Fresh Farm Traders']);

        $this->actingAs($this->admin)
            ->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSee('Suppliers')
            ->assertSee('Fresh Farm Traders');

        $this->actingAs($this->admin)
            ->get(route('admin.suppliers.create'))
            ->assertOk()
            ->assertSee('Create Supplier');

        $this->actingAs($this->admin)
            ->get(route('admin.suppliers.edit', $supplier))
            ->assertOk()
            ->assertSee('Edit Supplier')
            ->assertSee('Fresh Farm Traders');

        $this->actingAs($this->admin)
            ->get(route('admin.suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Fresh Farm Traders')
            ->assertSee('Recent Purchases');
    }

    public function test_admin_can_create_supplier(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.suppliers.store'), [
                'name' => 'Green Basket Supply',
                'contact_person' => 'Ravi Kumar',
                'mobile' => '+91 9876543210',
                'email' => 'supplier@example.com',
                'gstin' => ' 27abcde1234f1z5 ',
                'address' => 'Market Road',
                'city' => 'Pune',
                'state' => 'Maharashtra',
                'pincode' => '411001',
                'opening_balance' => 500,
                'status' => 'active',
                'notes' => 'Preferred supplier',
            ])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Green Basket Supply',
            'gstin' => '27ABCDE1234F1Z5',
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Old Supplier']);

        $this->actingAs($this->admin)
            ->patch(route('admin.suppliers.update', $supplier), [
                'name' => 'Updated Supplier',
                'mobile' => '9876543210',
                'email' => 'updated@example.com',
                'gstin' => '29abcde1234f1z5',
                'opening_balance' => 0,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier',
            'gstin' => '29ABCDE1234F1Z5',
            'status' => 'inactive',
        ]);
    }

    public function test_inactive_supplier_hidden_from_purchase_dropdown(): void
    {
        $active = Supplier::factory()->create(['name' => 'Active Supplier']);
        $inactive = Supplier::factory()->inactive()->create(['name' => 'Inactive Supplier']);
        $this->variant();

        $this->actingAs($this->admin)
            ->get(route('admin.purchases.create'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_supplier_used_in_purchase_cannot_be_deleted(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Purchase Supplier']);
        $this->purchaseFor($supplier);

        $this->actingAs($this->admin)
            ->delete(route('admin.suppliers.destroy', $supplier))
            ->assertRedirect(route('admin.suppliers.index'))
            ->assertSessionHasErrors('supplier');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_detail_shows_recent_purchases(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Recent Supplier']);
        $purchase = $this->purchaseFor($supplier, ['purchase_number' => 'PUR-RECENT-001']);

        $this->actingAs($this->admin)
            ->get(route('admin.suppliers.show', $supplier))
            ->assertOk()
            ->assertSee('Recent Supplier')
            ->assertSee($purchase->purchase_number)
            ->assertSee('Rs. 120.00');
    }

    private function purchaseFor(Supplier $supplier, array $overrides = []): PurchaseEntry
    {
        return PurchaseEntry::query()->create(array_merge([
            'supplier_id' => $supplier->id,
            'purchase_number' => 'PUR-TEST-'.fake()->unique()->numerify('####'),
            'bill_number' => 'BILL-1',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 100,
            'gst_total' => 20,
            'discount_total' => 0,
            'grand_total' => 120,
            'status' => PurchaseEntry::STATUS_POSTED,
        ], $overrides));
    }

    private function variant(): ProductVariant
    {
        StockLocation::factory()->create([
            'name' => 'Main Store',
            'code' => 'MAIN',
            'is_default' => true,
            'status' => true,
        ]);

        $product = Product::factory()->create(['status' => true, 'name' => 'Rice']);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);

        return $variant;
    }
}
