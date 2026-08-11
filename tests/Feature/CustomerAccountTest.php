<?php

namespace Tests\Feature;

use App\Domains\Customer\Services\CustomerAddressService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerLoginOtp;
use App\Models\Inventory;
use App\Models\Notification;
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
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
        app(BusinessSettingService::class)->set('storefront.allow_guest_checkout', true);
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

    public function test_setting_default_address_works_and_ui_renders_state(): void
    {
        $customer = Customer::factory()->create();
        $home = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'label' => 'Home', 'is_default' => true]);
        $office = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'label' => 'Office', 'is_default' => false]);

        $this->withSession(['customer_id' => $customer->id])
            ->patch(route('customer.addresses.default', $office))
            ->assertRedirect();

        $this->assertFalse($home->fresh()->is_default);
        $this->assertTrue($office->fresh()->is_default);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.addresses.index'))
            ->assertOk()
            ->assertSee('Default Address')
            ->assertSee('badge text-bg-success rounded-pill px-3 py-2', false)
            ->assertSee('Set Default');
    }

    public function test_checkout_prefills_default_approved_address(): void
    {
        $customer = Customer::factory()->create(['name' => 'Rohit Kumar', 'mobile' => '9876543210']);
        $nonDefaultAddress = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'address_line1' => 'Non Default Address',
            'is_default' => false,
            'is_approved' => true,
        ]);
        $defaultAddress = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'address_line1' => '42 Default Street',
            'address_line2' => 'Near Park',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'landmark' => 'Clock Tower',
            'is_default' => true,
            'is_approved' => true,
        ]);
        [, $variant] = $this->purchasableVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Delivery Address')
            ->assertSee('customer_address_id', false)
            ->assertSee('value="'.$nonDefaultAddress->id.'"', false)
            ->assertSee('data-line1="Non Default Address"', false)
            ->assertSee('id="customerAddress'.$defaultAddress->id.'"', false)
            ->assertSee('value="'.$defaultAddress->id.'"', false)
            ->assertSee('data-line1="42 Default Street"', false)
            ->assertSee('checked', false)
            ->assertSee('value="42 Default Street"', false)
            ->assertSee('value="Near Park"', false)
            ->assertSee('value="Patna"', false)
            ->assertSee('value="Bihar"', false)
            ->assertSee('value="800001"', false)
            ->assertSee('value="Clock Tower"', false);
    }

    public function test_checkout_shows_only_approved_address_chips_and_excludes_unapproved_addresses(): void
    {
        $customer = Customer::factory()->create();
        $approved = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Home',
            'address_line1' => 'Approved Street',
            'is_default' => true,
            'is_approved' => true,
        ]);
        CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Pending',
            'address_line1' => 'Pending Street',
            'is_approved' => false,
        ]);
        [, $variant] = $this->purchasableVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('value="'.$approved->id.'"', false)
            ->assertSee('Approved Street')
            ->assertDontSee('Pending Street');
    }

    public function test_checkout_with_one_approved_address_shows_current_address_without_selector_chips(): void
    {
        $customer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Office',
            'address_line1' => 'Single Approved Street',
            'is_approved' => true,
        ]);
        [, $variant] = $this->purchasableVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Office')
            ->assertSee('Single Approved Street')
            ->assertSee('name="customer_address_id" value="'.$address->id.'"', false)
            ->assertDontSee('btn-check js-address-choice', false);
    }

    public function test_logged_in_checkout_uses_approved_address_snapshot_and_ignores_forged_text(): void
    {
        $customer = Customer::factory()->create(['mobile' => '9876543210']);
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'address_line1' => 'Approved Checkout Street',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'landmark' => 'Approved Landmark',
            'is_approved' => true,
            'status' => true,
        ]);
        [, $variant] = $this->purchasableVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
                'customer_address_id' => $address->id,
                'delivery_address_line1' => 'Forged Street',
                'delivery_city' => 'Forged City',
            ]))
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('Approved Checkout Street', $order->delivery_address_line1);
        $this->assertSame('Patna', $order->delivery_city);
        $this->assertSame('Approved Landmark', $order->delivery_landmark);
    }

    public function test_logged_in_checkout_rejects_foreign_unapproved_and_missing_approved_addresses(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        $foreignAddress = CustomerAddress::factory()->create(['customer_id' => $other->id, 'is_approved' => true]);
        $unapprovedAddress = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_approved' => false]);

        [, $variant] = $this->purchasableVariant();
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), ['customer_address_id' => $foreignAddress->id]))
            ->assertSessionHasErrors('checkout');

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), ['customer_address_id' => $unapprovedAddress->id]))
            ->assertSessionHasErrors('checkout');

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload())
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_customer_without_approved_address_sees_checkout_block_message(): void
    {
        $customer = Customer::factory()->create();
        CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_approved' => false]);
        [, $variant] = $this->purchasableVariant();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('No approved delivery address is available')
            ->assertSee('My Addresses');
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
        $address = CustomerAddress::factory()->create(['customer_id' => $customer->id, 'is_approved' => true]);
        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $customer->id])->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
            'customer_address_id' => $address->id,
        ]))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id]);
        $this->withSession(['customer_id' => $customer->id])->get(route('checkout.show'))->assertRedirect(route('cart.show'));
    }

    public function test_customer_can_cancel_eligible_order_with_reason(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'order_status' => 'placed']);

        $this->withSession(['customer_id' => $customer->id])
            ->patch(route('customer.orders.cancel', $order->order_number), ['reason' => 'Ordered by mistake'])
            ->assertRedirect(route('customer.orders.show', $order->order_number));

        $this->assertSame('cancelled_by_customer', $order->fresh()->order_status);
        $this->assertSame('Ordered by mistake', $order->fresh()->admin_notes);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Cancellation reason')
            ->assertSee('Ordered by mistake');
    }

    public function test_customer_cannot_cancel_ineligible_order(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'order_status' => 'picking']);

        $this->withSession(['customer_id' => $customer->id])
            ->from(route('customer.orders.show', $order->order_number))
            ->patch(route('customer.orders.cancel', $order->order_number), ['reason' => 'Too late'])
            ->assertRedirect(route('customer.orders.show', $order->order_number))
            ->assertSessionHasErrors('order');

        $this->assertSame('picking', $order->fresh()->order_status);
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
        $this->assertNotContains('cashback', $uris);
        $this->assertNotContains('coupons', $uris);
    }

    public function test_admin_address_approval_shows_full_address_and_notifies_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Rohit Kumar', 'mobile' => '9876543210']);
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Home',
            'recipient_name' => 'Rohit Delivery',
            'mobile' => '9000000000',
            'address_line1' => 'House 12',
            'address_line2' => 'Near Market',
            'city' => 'Patna',
            'state' => 'Bihar',
            'pincode' => '800001',
            'landmark' => 'Clock Tower',
            'is_approved' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Home')
            ->assertSee('Rohit Delivery')
            ->assertSee('House 12')
            ->assertSee('Near Market')
            ->assertSee('Clock Tower')
            ->assertSee('Patna, Bihar - 800001')
            ->assertSee('9000000000');

        $this->actingAs($this->admin)
            ->patch(route('admin.customers.addresses.approve', [$customer, $address]))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'address.approved',
            'message' => 'Your Home delivery address has been approved.',
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.customers.addresses.approve', [$customer, $address->fresh()]))
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'customer_id' => $customer->id,
            'type' => 'address.unapproved',
            'message' => 'Your Home delivery address is no longer approved for delivery.',
        ]);
    }

    public function test_unchanged_address_approval_status_does_not_duplicate_notification(): void
    {
        $customer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'label' => 'Home',
            'is_approved' => true,
        ]);

        app(CustomerAddressService::class)->approve($address, true);

        $this->assertSame(0, Notification::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'address.approved')
            ->count());
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
            'payment_method' => 'cod',
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
