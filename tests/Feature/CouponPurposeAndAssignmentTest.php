<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\DeliverySlot;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponPurposeAndAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(BusinessSettingSeeder::class);
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
        app(BusinessSettingService::class)->set('storefront.allow_guest_checkout', true);
        app(BusinessSettingService::class)->set('checkout.delivery_charge', 150)->update(['value_type' => 'decimal']);
        DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9 AM - 11 AM',
            'status' => true,
        ]);
    }

    public function test_merchandise_coupon_affects_merchandise_only(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $coupon = Coupon::factory()->create([
            'code' => 'SAVE50',
            'purpose' => Coupon::PURPOSE_MERCHANDISE,
            'discount_type' => 'fixed',
            'discount_value' => 50,
        ]);

        $this->addAndApply($customer, $variant, $coupon->code);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address))
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('50.00', $order->discount_total);
        $this->assertSame('0.00', $order->delivery_discount_total);
        $this->assertSame('150.00', $order->delivery_charge);
        $this->assertSame('168.00', $order->grand_total);
    }

    public function test_free_delivery_coupon_affects_delivery_only(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $coupon = Coupon::factory()->create([
            'code' => 'FREEDEL',
            'purpose' => Coupon::PURPOSE_FREE_DELIVERY,
            'discount_type' => 'fixed',
            'discount_value' => 1,
        ]);

        $this->addAndApply($customer, $variant, $coupon->code);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address))
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('0.00', $order->discount_total);
        $this->assertSame('150.00', $order->original_delivery_charge);
        $this->assertSame('150.00', $order->delivery_discount_total);
        $this->assertSame('0.00', $order->delivery_charge);
        $this->assertSame('68.00', $order->grand_total);
    }

    public function test_delivery_fixed_and_percent_coupons_reduce_delivery_only(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $fixed = Coupon::factory()->create([
            'code' => 'DEL50',
            'purpose' => Coupon::PURPOSE_DELIVERY_FIXED,
            'discount_type' => 'fixed',
            'discount_value' => 50,
        ]);

        $this->addAndApply($customer, $variant, $fixed->code);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address))
            ->assertRedirect();

        $this->assertSame('50.00', Order::query()->firstOrFail()->delivery_discount_total);

        [$customer, $address] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $percent = Coupon::factory()->create([
            'code' => 'DEL25',
            'purpose' => Coupon::PURPOSE_DELIVERY_PERCENT,
            'discount_type' => 'percentage',
            'discount_value' => 25,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.coupon.apply'), ['code' => $percent->code]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address))
            ->assertRedirect();

        $this->assertSame('37.50', Order::query()->latest('id')->firstOrFail()->delivery_discount_total);
    }

    public function test_delivery_coupon_rejected_when_delivery_is_already_free(): void
    {
        app(BusinessSettingService::class)->set('checkout.delivery_charge', 0)->update(['value_type' => 'decimal']);
        [$customer] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $coupon = Coupon::factory()->create([
            'code' => 'FREEDEL',
            'purpose' => Coupon::PURPOSE_FREE_DELIVERY,
            'discount_type' => 'fixed',
            'discount_value' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.coupon.apply'), ['code' => $coupon->code])
            ->assertSessionHasErrors('coupon');

        $this->assertSame(0, $coupon->usages()->count());
    }

    public function test_assigned_coupon_rejected_for_unassigned_customer(): void
    {
        [$assigned] = $this->customerWithAddress();
        [$other] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $coupon = Coupon::factory()->create([
            'code' => 'PRIVATE',
            'audience' => Coupon::AUDIENCE_CUSTOMER_SPECIFIC,
        ]);
        $coupon->assignedCustomers()->attach($assigned->id, ['assigned_at' => now()]);

        $this->withSession(['customer_id' => $other->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $other->id])
            ->post(route('cart.coupon.apply'), ['code' => $coupon->code])
            ->assertSessionHasErrors('coupon');
    }

    public function test_second_valid_coupon_replaces_first_and_invalid_second_keeps_first(): void
    {
        [$customer] = $this->customerWithAddress();
        [, $variant] = $this->cartItem();
        $first = Coupon::factory()->create(['code' => 'FIRST', 'discount_value' => 10]);
        $second = Coupon::factory()->create(['code' => 'SECOND', 'discount_value' => 20]);

        $this->addAndApply($customer, $variant, $first->code);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.coupon.apply'), ['code' => 'MISSING'])
            ->assertSessionHasErrors('coupon');
        $this->assertDatabaseHas('carts', ['coupon_code' => 'FIRST']);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.coupon.apply'), ['code' => $second->code])
            ->assertSessionHas('success', 'Coupon replaced successfully.');

        $this->assertDatabaseHas('carts', ['coupon_code' => 'SECOND']);
    }

    private function addAndApply(Customer $customer, ProductVariant $variant, string $code): void
    {
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.coupon.apply'), ['code' => $code])
            ->assertSessionHasNoErrors();
    }

    private function customerWithAddress(): array
    {
        $customer = Customer::factory()->create();
        $address = CustomerAddress::factory()->create([
            'customer_id' => $customer->id,
            'is_approved' => true,
            'status' => true,
            'is_default' => true,
        ]);

        return [$customer, $address];
    }

    private function cartItem(): array
    {
        $product = Product::factory()->create(['status' => true, 'hsn_code' => '1101', 'gst_rate' => 5]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'mrp' => 75,
            'selling_price' => 68,
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        return [$product, $variant];
    }

    private function checkoutPayload(Customer $customer, CustomerAddress $address): array
    {
        return [
            'customer_name' => $customer->name,
            'customer_mobile' => $customer->mobile,
            'customer_email' => $customer->email,
            'customer_address_id' => $address->id,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => '9 AM - 11 AM',
            'payment_method' => 'cod',
        ];
    }
}
