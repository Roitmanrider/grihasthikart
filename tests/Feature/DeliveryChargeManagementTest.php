<?php

namespace Tests\Feature;

use App\Domains\Cashback\Services\CashbackCalculationService;
use App\Domains\Delivery\Services\DeliveryChargeService;
use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliverySlot;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryChargeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
        app(BusinessSettingService::class)->set('storefront.allow_guest_checkout', true);
        DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9 AM - 11 AM',
            'status' => true,
        ]);
    }

    public function test_standard_premium_guest_and_customer_override_precedence(): void
    {
        $this->deliverySettings(standardCharge: 100, standardThreshold: 500, premiumMinimum: 150, premiumCharge: 50, premiumThreshold: 750);
        $service = app(DeliveryChargeService::class);

        $standard = Customer::factory()->create();
        $premium = Customer::factory()->create(['is_premium' => true]);
        $standardOverride = Customer::factory()->create([
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 150,
        ]);
        $premiumOverride = Customer::factory()->create([
            'is_premium' => true,
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 120,
        ]);

        $this->assertSame(100.0, $service->resolve(null, 400)['delivery_charge']);
        $this->assertSame(100.0, $service->resolve($standard, 400)['delivery_charge']);
        $this->assertSame(50.0, $service->resolve($premium, 400)['delivery_charge']);
        $this->assertSame(150.0, $service->resolve($standardOverride, 400)['delivery_charge']);
        $this->assertSame(120.0, $service->resolve($premiumOverride, 400)['delivery_charge']);
        $this->assertSame('CUSTOMER_OVERRIDE', $service->resolve($premiumOverride, 400)['sources']['delivery_charge']);
    }

    public function test_customer_overrides_support_nullable_mixed_inheritance_and_zero_values(): void
    {
        $this->deliverySettings(standardMinimum: 200, standardCharge: 100, standardThreshold: 500, premiumMinimum: 150, premiumCharge: 50, premiumThreshold: 750);
        $service = app(DeliveryChargeService::class);
        $customer = Customer::factory()->create([
            'is_premium' => true,
            'custom_delivery_rules_enabled' => true,
            'minimum_order_amount_override' => 400,
            'delivery_charge_override' => null,
            'free_delivery_threshold_override' => 1000,
        ]);

        $rule = $service->resolve($customer, 760);

        $this->assertSame(400.0, $rule['minimum_order_amount']);
        $this->assertSame(50.0, $rule['delivery_charge']);
        $this->assertSame(1000.0, $rule['free_delivery_threshold']);
        $this->assertSame(240.0, $rule['free_delivery_remaining']);
        $this->assertSame('CUSTOMER_OVERRIDE', $rule['sources']['minimum_order_amount']);
        $this->assertSame('PREMIUM', $rule['sources']['delivery_charge']);
        $this->assertSame('CUSTOMER_OVERRIDE', $rule['sources']['free_delivery_threshold']);

        $freeCharge = Customer::factory()->create([
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 0,
        ]);
        $alwaysFree = Customer::factory()->create([
            'custom_delivery_rules_enabled' => true,
            'free_delivery_threshold_override' => 0,
        ]);

        $this->assertSame(0.0, $service->resolve($freeCharge, 10)['delivery_charge']);
        $this->assertSame(0.0, $service->resolve($alwaysFree, 10)['delivery_charge']);
    }

    public function test_cart_and_checkout_display_effective_customer_specific_rule(): void
    {
        $this->deliverySettings(standardCharge: 100, standardThreshold: 500);
        [$customer, $address] = $this->customerWithAddress([
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 150,
            'free_delivery_threshold_override' => 1000,
        ]);
        $variant = $this->variant(price: 600);

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('cart.show'))
            ->assertOk()
            ->assertSee('Rs. 150.00')
            ->assertSee('Add Rs. 400.00 more for FREE delivery');

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Rs. 150.00')
            ->assertSee('Add Rs. 400.00 more for FREE delivery')
            ->assertSee((string) $address->pincode);
    }

    public function test_checkout_uses_effective_rule_and_ignores_forged_client_delivery_charge(): void
    {
        $this->deliverySettings(standardCharge: 100, standardThreshold: 500);
        [$customer, $address] = $this->customerWithAddress([
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 150,
            'free_delivery_threshold_override' => 1000,
        ]);
        $variant = $this->variant(price: 600);

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), [
                'customer_address_id' => $address->id,
                'delivery_charge' => 0,
            ]))
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('150.00', $order->delivery_charge);
        $this->assertSame('750.00', $order->grand_total);
    }

    public function test_changing_customer_override_affects_future_checkout_only(): void
    {
        [$customer, $address] = $this->customerWithAddress([
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 60,
        ]);
        $variant = $this->variant(price: 600);

        $this->placeCustomerOrder($customer, $address, $variant);
        $firstOrder = Order::query()->firstOrFail();

        $customer->update(['delivery_charge_override' => 150]);
        $this->placeCustomerOrder($customer, $address, $variant);

        $this->assertSame('60.00', $firstOrder->fresh()->delivery_charge);
        $this->assertSame('150.00', Order::query()->latest('id')->firstOrFail()->delivery_charge);
    }

    public function test_customer_specific_minimum_order_is_enforced_server_side(): void
    {
        [$customer, $address] = $this->customerWithAddress([
            'custom_delivery_rules_enabled' => true,
            'minimum_order_amount_override' => 400,
        ]);
        $variant = $this->variant(price: 300);

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), ['customer_address_id' => $address->id]))
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_inactive_customer_delivery_rules_do_not_bypass_customer_status(): void
    {
        app(BusinessSettingService::class)->set('storefront.allow_guest_checkout', false);
        [$customer, $address] = $this->customerWithAddress([
            'status' => false,
            'custom_delivery_rules_enabled' => true,
            'delivery_charge_override' => 0,
        ]);
        $variant = $this->variant(price: 600);

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), ['customer_address_id' => $address->id]))
            ->assertRedirect(route('customer.login'));

        $this->assertSame(0, Order::query()->count());
    }

    public function test_delivery_charge_does_not_affect_gst_cashback_base_or_merchandise_subtotal(): void
    {
        $this->deliverySettings(standardCharge: 100);
        $variant = $this->variant(price: 105, gst: 5);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();

        $order = Order::query()->firstOrFail();

        $this->assertSame('105.00', $order->subtotal);
        $this->assertSame('100.00', $order->delivery_charge);
        $this->assertSame('205.00', $order->grand_total);
        $this->assertSame('5.00', $order->tax_total);
        $this->assertSame(105.0, app(CashbackCalculationService::class)->cashbackBase($order));
    }

    public function test_exact_free_delivery_threshold_boundary(): void
    {
        $this->deliverySettings(standardCharge: 100, standardThreshold: 500);
        $service = app(DeliveryChargeService::class);

        $this->assertSame(100.0, $service->resolve(null, 499.99)['delivery_charge']);
        $this->assertSame(0.0, $service->resolve(null, 500)['delivery_charge']);
        $this->assertSame(0.0, $service->resolve(null, 500.01)['delivery_charge']);
    }

    public function test_admin_customer_ui_saves_and_displays_effective_delivery_rules(): void
    {
        $this->deliverySettings(standardMinimum: 200, standardCharge: 100, standardThreshold: 500);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $customer = Customer::factory()->create();

        $this->actingAs($admin)->patch(route('admin.customers.update', $customer), [
            'name' => $customer->name,
            'mobile' => $customer->mobile,
            'email' => $customer->email,
            'status' => 1,
            'is_premium' => 0,
            'cashback_enabled' => 0,
            'custom_delivery_rules_enabled' => 1,
            'minimum_order_amount_override' => '',
            'delivery_charge_override' => 150,
            'free_delivery_threshold_override' => '',
        ])->assertRedirect(route('admin.customers.show', $customer));

        $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Delivery Rules')
            ->assertSee('Custom Delivery')
            ->assertSee('Effective Delivery Charge')
            ->assertSee('Rs. 150.00');
    }

    private function deliverySettings(
        float $standardMinimum = 0,
        float $standardCharge = 0,
        ?float $standardThreshold = null,
        ?float $premiumMinimum = null,
        ?float $premiumCharge = null,
        ?float $premiumThreshold = null
    ): void {
        foreach ([
            'minimum_order_amount' => $standardMinimum,
            'delivery_charge' => $standardCharge,
            'free_delivery_threshold' => $standardThreshold,
            'premium_minimum_order_amount' => $premiumMinimum,
            'premium_delivery_charge' => $premiumCharge,
            'premium_free_delivery_threshold' => $premiumThreshold,
        ] as $key => $value) {
            app(BusinessSettingService::class)->set('checkout.'.$key, $value)
                ->update(['value_type' => 'decimal']);
        }
    }

    private function customerWithAddress(array $overrides = []): array
    {
        $customer = Customer::factory()->create($overrides);
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_approved' => true,
            'status' => true,
            'is_default' => true,
        ]);

        return [$customer, $address];
    }

    private function variant(float $price, float $gst = 0): ProductVariant
    {
        $product = Product::factory()->create([
            'name' => 'Delivery Test Product',
            'status' => true,
            'gst_rate' => $gst,
        ]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => 'Pack',
            'mrp' => $price,
            'selling_price' => $price,
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 20,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        return $variant;
    }

    private function placeCustomerOrder(Customer $customer, CustomerAddress $address, ProductVariant $variant): void
    {
        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), array_merge($this->checkoutPayload(), ['customer_address_id' => $address->id]))
            ->assertRedirect();
    }

    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'customer_email' => 'rohit@example.com',
            'delivery_address_line1' => 'House 12, Main Road',
            'delivery_address_line2' => 'Near Market',
            'delivery_city' => 'Patna',
            'delivery_state' => 'Bihar',
            'delivery_pincode' => '800001',
            'delivery_landmark' => 'Clock Tower',
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => '9 AM - 11 AM',
            'payment_method' => 'cod',
            'notes' => 'Please call before delivery.',
        ];
    }
}
