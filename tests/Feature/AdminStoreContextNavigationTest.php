<?php

namespace Tests\Feature;

use App\Domains\Store\Services\AdminStoreContextService;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreContextNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_super_admin_sees_store_navigation_and_context_selector(): void
    {
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $mainStore = StockLocation::factory()->default()->create(['name' => 'Main Store']);
        $northStore = StockLocation::factory()->create(['name' => 'North Store']);
        StockLocation::factory()->inactive()->create(['name' => 'Closed Store']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.stores.index'))
            ->assertOk()
            ->assertSee('Stores')
            ->assertSee('Staff / Employees')
            ->assertSee('Store Context')
            ->assertSee('All Stores')
            ->assertSee('Main Store')
            ->assertSee('North Store');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Store Context')
            ->assertSee('All Stores')
            ->assertSee('Main Store');

        $this->actingAs($admin)
            ->patch(route('admin.store-context.update'), ['stock_location_id' => $northStore->id])
            ->assertRedirect();

        $this->assertSame($northStore->id, session(AdminStoreContextService::SESSION_KEY));

        $this->actingAs($admin)
            ->get(route('admin.daily-offers.index'))
            ->assertOk()
            ->assertSee('Store: North Store')
            ->assertDontSee('Select a store from the top bar before creating or changing Daily Offers.');

        $this->actingAs($admin)
            ->patch(route('admin.store-context.update'), ['stock_location_id' => $mainStore->id])
            ->assertRedirect();

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Store Context');
    }

    public function test_store_manager_sees_fixed_store_context_and_not_store_crud_navigation(): void
    {
        $store = StockLocation::factory()->default()->create(['name' => 'Main Store']);
        $manager = User::factory()->create([
            'role' => 'STORE_MANAGER',
            'assigned_store_id' => $store->id,
        ]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => Product::factory()->create()->id,
        ]);
        Inventory::factory()->create([
            'stock_location_id' => $store->id,
            'product_variant_id' => $variant->id,
            'status' => true,
        ]);

        $this->actingAs($manager)
            ->get(route('admin.stores.index'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('admin.inventories.index'))
            ->assertOk()
            ->assertSee('Store:')
            ->assertSee('Main Store')
            ->assertDontSee('Store Context')
            ->assertDontSee('Staff / Employees')
            ->assertDontSee('href="'.route('admin.stores.index').'"', false);
    }

    public function test_super_admin_can_create_operational_staff_and_unauthorized_user_cannot(): void
    {
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $store = StockLocation::factory()->default()->create();
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $manager = User::factory()->create(['role' => 'STORE_MANAGER', 'assigned_store_id' => $store->id]);

        $this->actingAs($admin)
            ->get(route('admin.staff.create'))
            ->assertOk()
            ->assertSee('Add Staff')
            ->assertSee('Delivery Agent')
            ->assertSee('Denied Permission Overrides');

        $this->actingAs($admin)
            ->post(route('admin.staff.store'), [
                'name' => 'Delivery One',
                'email' => 'delivery@example.com',
                'password' => 'password123',
                'assigned_store_id' => $store->id,
                'staff_active' => 1,
                'staff_roles' => ['DELIVERY_AGENT', 'PICKER_PACKER'],
                'additional_permissions' => ['approvals.delivery_override'],
                'denied_permissions' => ['delivery.mark_failed'],
            ])
            ->assertRedirect(route('admin.staff.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'delivery@example.com',
            'assigned_store_id' => $store->id,
            'staff_active' => true,
        ]);
        $staff = User::query()->where('email', 'delivery@example.com')->firstOrFail();
        $this->assertSame(['DELIVERY_AGENT', 'PICKER_PACKER'], $staff->staff_roles);
        $this->assertSame(['approvals.delivery_override'], $staff->additional_permissions);
        $this->assertSame(['delivery.mark_failed'], $staff->denied_permissions);

        $this->actingAs($manager)
            ->patch(route('admin.staff.update', $staff), ['staff_roles' => ['STORE_MANAGER']])
            ->assertForbidden();
    }

    public function test_staff_portal_admission_requires_active_operational_staff_role(): void
    {
        $store = StockLocation::factory()->default()->create();
        $activeStaff = User::factory()->create([
            'assigned_store_id' => $store->id,
            'staff_roles' => ['PICKER_PACKER'],
            'staff_active' => true,
        ]);
        $inactiveStaff = User::factory()->create([
            'assigned_store_id' => $store->id,
            'staff_roles' => ['PICKER_PACKER'],
            'staff_active' => false,
        ]);
        $plainUser = User::factory()->create(['staff_active' => true]);
        $superAdmin = User::factory()->create(['role' => 'SUPER_ADMIN', 'staff_active' => true]);

        $this->get(route('staff.dashboard'))
            ->assertRedirect(route('staff.login'));

        $this->actingAs($activeStaff)
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Staff Dashboard');

        $this->actingAs($inactiveStaff)
            ->get(route('staff.dashboard'))
            ->assertForbidden();

        $this->actingAs($plainUser)
            ->get(route('staff.dashboard'))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('staff.dashboard'))
            ->assertForbidden();
    }
}
