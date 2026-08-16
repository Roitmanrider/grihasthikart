<?php

namespace Tests\Feature;

use App\Domains\Customer\Services\CustomerAuthService;
use App\Domains\Customer\Services\CustomerSessionService;
use App\Models\Customer;
use App\Models\CustomerSession;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Tests\TestCase;

class CustomerEntitlementSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
    }

    public function test_cashback_entitlement_and_customer_badges_render_correctly(): void
    {
        $standard = Customer::factory()->create([
            'is_premium' => false,
            'cashback_enabled' => false,
        ]);
        $premium = Customer::factory()->create([
            'is_premium' => true,
            'cashback_enabled' => true,
        ]);

        $this->withSession(['customer_id' => $standard->id])
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee('Premium Member')
            ->assertDontSee('gk-premium-badge', false)
            ->assertDontSee('Cashback Points')
            ->assertDontSee(route('customer.cashback.index'), false);

        $this->withSession(['customer_id' => $standard->id])
            ->get(route('customer.cashback.index'))
            ->assertRedirect(route('customer.dashboard'))
            ->assertSessionHas('errors');

        $this->flushSession();

        $this->withSession(['customer_id' => $premium->id])
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Premium Member')
            ->assertSee('gk-premium-badge', false)
            ->assertSee('Cashback Points')
            ->assertSee(route('customer.cashback.index'), false)
            ->assertDontSee('Standard');
    }

    public function test_inactive_customer_cannot_login_and_sees_clear_message(): void
    {
        Customer::factory()->inactive()->create(['mobile' => '9000000000']);

        $this->post(route('customer.login.request'), ['mobile' => '9000000000'])
            ->assertSessionHasErrors(['mobile' => 'Your account is currently inactive. Please contact GrihasthiKart support.']);

        $this->post(route('customer.login.request'), ['mobile' => '9111111111'])
            ->assertSessionHasErrors(['mobile' => 'Your mobile number is not registered with GrihasthiKart.']);
    }

    public function test_logged_in_customer_becoming_inactive_is_logged_out_and_cannot_continue(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $customer = Customer::factory()->create(['status' => true]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.dashboard'))
            ->assertOk();

        $this->actingAs($admin)
            ->patch(route('admin.customers.status', $customer))
            ->assertRedirect();

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('customer.login'))
            ->assertSessionHasErrors(['customer' => 'Your account is currently inactive. Please contact GrihasthiKart support.']);

        $this->assertSame(0, CustomerSession::query()->where('customer_id', $customer->id)->active()->count());
    }

    public function test_customer_session_policy_allows_two_devices_and_revokes_oldest_on_third(): void
    {
        $customer = Customer::factory()->create();
        $service = app(CustomerSessionService::class);

        $first = $this->sessionStore('first');
        $second = $this->sessionStore('second');
        $third = $this->sessionStore('third');
        $first->put('customer_id', $customer->id);
        $second->put('customer_id', $customer->id);
        $third->put('customer_id', $customer->id);

        $service->start($customer, $first);
        $service->start($customer, $second);
        $service->start($customer, $third);

        $this->assertSame(2, CustomerSession::query()->where('customer_id', $customer->id)->active()->count());
        $this->assertNull($service->validate($first));
        $this->assertNotNull($service->validate($second));
        $this->assertNotNull($service->validate($third));
        $this->assertDatabaseHas('notifications', [
            'customer_id' => $customer->id,
            'type' => 'customer.session_replaced',
        ]);
    }

    public function test_twenty_one_day_absolute_expiry_forces_relogin_and_activity_does_not_extend_it(): void
    {
        $customer = Customer::factory()->create();
        $service = app(CustomerSessionService::class);
        $session = $this->sessionStore('expiring');
        $session->put('customer_id', $customer->id);

        $this->travelTo(now());
        $tracked = $service->start($customer, $session);
        $expiresAt = $tracked->expires_at->copy();

        $this->travel(10)->days();
        $this->assertNotNull($service->validate($session));
        $this->assertTrue($tracked->fresh()->expires_at->equalTo($expiresAt));

        $this->travelTo($expiresAt->copy()->addSecond());
        $this->assertNull($service->validate($session));

        $this->travelBack();
    }

    public function test_logout_revokes_current_customer_session(): void
    {
        $customer = Customer::factory()->create();
        $session = $this->sessionStore('logout');
        app(CustomerSessionService::class)->start($customer, $session);
        $session->put('customer_id', $customer->id);

        app(CustomerAuthService::class)->logout($session);

        $this->assertSame(0, CustomerSession::query()->where('customer_id', $customer->id)->active()->count());
    }

    private function sessionStore(string $name): Store
    {
        $session = new Store($name, new ArraySessionHandler(120));
        $session->start();

        return $session;
    }
}
