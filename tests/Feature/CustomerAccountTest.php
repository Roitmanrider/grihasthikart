<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerLoginOtp;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_create_customer_and_mobile_must_be_unique(): void
    {
        $this->actingAs($this->admin)->post(route('admin.customers.store'), [
            'name' => 'Rohit Kumar',
            'mobile' => '9876543210',
            'email' => 'rohit@example.com',
            'status' => 1,
        ])->assertRedirect(route('admin.customers.index'));

        $this->assertDatabaseHas('customers', ['mobile' => '9876543210']);

        $this->actingAs($this->admin)->post(route('admin.customers.store'), [
            'name' => 'Duplicate',
            'mobile' => '9876543210',
        ])->assertSessionHasErrors('mobile');
    }

    public function test_inactive_customer_cannot_login_and_active_customer_can_verify_otp_once(): void
    {
        Customer::factory()->inactive()->create(['mobile' => '9000000000']);
        $customer = Customer::factory()->create(['mobile' => '9876543210']);

        $this->post(route('customer.login.request'), ['mobile' => '9000000000'])
            ->assertSessionHasErrors('mobile');

        $this->post(route('customer.login.request'), ['mobile' => '9876543210'])
            ->assertRedirect(route('customer.otp.verify.form', ['mobile' => '9876543210']));

        $this->post(route('customer.otp.verify'), ['mobile' => '9876543210', 'otp' => '000000'])
            ->assertSessionHasErrors('otp');

        $this->post(route('customer.otp.verify'), ['mobile' => '9876543210', 'otp' => '123456'])
            ->assertRedirect(route('customer.dashboard'));

        $this->assertSame($customer->id, session('customer_id'));

        $this->post(route('customer.otp.verify'), ['mobile' => '9876543210', 'otp' => '123456'])
            ->assertSessionHasErrors('otp');
    }

    public function test_expired_otp_is_rejected_and_customer_can_logout(): void
    {
        $customer = Customer::factory()->create(['mobile' => '9876543210']);
        CustomerLoginOtp::query()->create([
            'customer_id' => $customer->id,
            'mobile' => $customer->mobile,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(),
        ]);

        $this->post(route('customer.otp.verify'), ['mobile' => $customer->mobile, 'otp' => '123456'])
            ->assertSessionHasErrors('otp');

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.logout'))->assertRedirect(route('home'));
        $this->assertNull(session('customer_id'));
    }

    public function test_customer_dashboard_requires_login_and_customer_can_manage_own_addresses(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $otherAddress = CustomerAddress::factory()->create(['customer_id' => $other->id]);

        $this->get(route('customer.dashboard'))->assertRedirect(route('customer.login'));

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.addresses.store'), $this->addressPayload(['label' => 'Home', 'is_default' => 1]))
            ->assertRedirect(route('customer.addresses.index'));

        $first = CustomerAddress::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertTrue($first->is_default);

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.addresses.store'), $this->addressPayload(['label' => 'Office', 'is_default' => 1]));
        $this->assertSame(1, CustomerAddress::query()->where('customer_id', $customer->id)->where('is_default', true)->count());

        $this->withSession(['customer_id' => $customer->id])->get(route('customer.addresses.edit', $otherAddress))->assertNotFound();
    }

    public function test_customer_order_history_shows_only_own_orders_and_checkout_sets_customer_id(): void
    {
        $customer = Customer::factory()->create(['mobile' => '9876543210']);
        $ownOrder = Order::factory()->create(['customer_id' => $customer->id, 'order_number' => 'GKOWN']);
        Order::factory()->create(['order_number' => 'GKOTHER']);

        $this->withSession(['customer_id' => $customer->id])->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee('GKOWN')
            ->assertDontSee('GKOTHER');

        [, $variant] = $this->purchasableVariant();
        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $customer->id])->post(route('checkout.place'), $this->checkoutPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id]);
        $this->withSession(['customer_id' => $customer->id])->get(route('checkout.show'))->assertRedirect(route('cart.show'));
    }

    public function test_session_cart_attaches_to_customer_after_login_and_guest_checkout_still_works(): void
    {
        $customer = Customer::factory()->create(['mobile' => '9876543210']);
        [, $variant] = $this->purchasableVariant();

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('customer.login.request'), ['mobile' => $customer->mobile]);
        $this->post(route('customer.otp.verify'), ['mobile' => $customer->mobile, 'otp' => '123456']);

        $this->assertSame($customer->id, Cart::query()->active()->firstOrFail()->customer_id);

        [, $guestVariant] = $this->purchasableVariant();
        $this->post(route('cart.items.store'), ['product_variant_id' => $guestVariant->id, 'quantity' => 1]);
        $this->post(route('customer.logout'));
        $this->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();
        $this->assertDatabaseHas('orders', ['customer_id' => null]);
    }

    public function test_admin_customer_routes_require_manage_customers_and_no_disallowed_modules(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $this->actingAs($user)->get(route('admin.customers.index'))->assertForbidden();

        $uris = collect(Route::getRoutes())->map(fn ($route) => $route->uri())->all();
        $this->assertNotContains('razorpay', $uris);
        $this->assertNotContains('cashback', $uris);
        $this->assertNotContains('coupons', $uris);
    }

    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'recipient_name' => 'Rohit Kumar',
            'mobile' => '9876543210',
            'address_line1' => 'House 12',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
        ], $overrides);
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'delivery_address_line1' => 'House 12',
            'delivery_city' => 'Patna',
            'delivery_state' => 'Bihar',
            'delivery_pincode' => '800001',
        ];
    }

    private function purchasableVariant(): array
    {
        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'status' => true, 'selling_price' => 68, 'mrp' => 75]);
        $product->update(['default_variant_id' => $variant->id]);
        Inventory::factory()->create(['product_variant_id' => $variant->id, 'quantity_on_hand' => 10, 'reserved_quantity' => 0, 'damaged_quantity' => 0]);

        return [$product, $variant];
    }
}
