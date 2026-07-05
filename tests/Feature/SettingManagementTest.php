<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Models\BusinessSetting;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_and_update_checkout_settings(): void
    {
        $this->actingAs($this->admin)->get(route('admin.settings.checkout.edit'))
            ->assertOk()
            ->assertSee('Checkout Settings');

        $this->actingAs($this->admin)->put(route('admin.settings.checkout.update'), [
            'minimum_order_amount' => 499,
            'delivery_charge' => 25,
            'cod_enabled' => 1,
            'today_delivery_enabled' => 0,
            'today_delivery_cutoff_time' => '13:30',
            'custom_delivery_date_enabled' => 1,
            'max_delivery_days_ahead' => 5,
            'default_state' => 'Bihar',
            'default_city' => 'Patna',
            'store_contact_mobile' => '9876543210',
            'store_whatsapp_number' => '9876543210',
        ])->assertRedirect(route('admin.settings.checkout.edit'));

        $service = app(BusinessSettingService::class);
        $this->assertSame(499.0, $service->get('checkout.minimum_order_amount'));
        $this->assertSame(25.0, $service->get('checkout.delivery_charge'));
        $this->assertFalse($service->get('checkout.today_delivery_enabled'));
        $this->assertSame(5, $service->get('checkout.max_delivery_days_ahead'));
    }

    public function test_settings_are_type_cast_correctly(): void
    {
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'cod_enabled')->update(['value' => '0']);
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'delivery_charge')->update(['value' => '49.50']);
        BusinessSetting::query()->where('group', 'checkout')->where('key', 'max_delivery_days_ahead')->update(['value' => '3']);

        $service = app(BusinessSettingService::class);

        $this->assertFalse($service->get('checkout.cod_enabled'));
        $this->assertSame(49.5, $service->get('checkout.delivery_charge'));
        $this->assertSame(3, $service->get('checkout.max_delivery_days_ahead'));
    }

    public function test_settings_routes_require_authorization(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)->get(route('admin.settings.checkout.edit'))->assertForbidden();
    }
}
