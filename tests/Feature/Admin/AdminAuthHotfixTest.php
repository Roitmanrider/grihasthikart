<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthHotfixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
    }

    public function test_guests_redirect_to_admin_login_without_missing_login_route_error(): void
    {
        $this->get('/login')
            ->assertRedirect(route('admin.login'));

        $this->get('/admin')
            ->assertRedirect(route('admin.login'));

        $this->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_invalid_and_unauthorized_admin_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);
        $regularUser = User::factory()->create(['email' => 'regular@example.com']);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->post(route('admin.login.submit'), [
            'email' => $regularUser->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_valid_admin_can_login_access_dashboard_and_logout(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->post(route('admin.login.submit'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Admin Dashboard');

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_customer_session_does_not_grant_admin_access(): void
    {
        $customer = Customer::factory()->create();

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_access_key_admin_modules(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        foreach ([
            route('admin.categories.index'),
            route('admin.brands.index'),
            route('admin.attributes.index'),
            route('admin.attribute-values.index'),
            route('admin.products.index'),
            route('admin.inventories.index'),
            route('admin.orders.index'),
            route('admin.payments.index'),
            route('admin.coupons.index'),
            route('admin.cashback.index'),
            route('admin.reports.gst-summary'),
            route('admin.settings.checkout.edit'),
            route('admin.settings.payments.edit'),
            route('admin.delivery-slots.index'),
            route('admin.customers.index'),
        ] as $route) {
            $this->actingAs($admin)->get($route)->assertOk();
        }
    }
}
