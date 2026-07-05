<?php

namespace Tests\Feature;

use App\Domains\Coupon\Services\CouponService;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\DeliverySlot;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CouponManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9-11 AM',
            'status' => true,
        ]);
    }

    public function test_admin_can_create_coupon_and_code_is_normalized(): void
    {
        $this->actingAs($this->admin)->post(route('admin.coupons.store'), $this->couponPayload([
            'code' => ' save10 ',
            'discount_type' => 'percentage',
            'discount_value' => 10,
        ]))->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', [
            'code' => 'SAVE10',
            'discount_type' => 'percentage',
        ]);

        $this->actingAs($this->admin)->post(route('admin.coupons.store'), $this->couponPayload([
            'code' => 'SAVE10',
        ]))->assertSessionHasErrors('code');
    }

    public function test_fixed_percentage_and_max_discount_calculation(): void
    {
        [$cart] = $this->cartWithItem(quantity: 5);
        $service = app(CouponService::class);

        $fixed = Coupon::factory()->create(['discount_type' => 'fixed', 'discount_value' => 50]);
        $percentage = Coupon::factory()->percentage()->create(['discount_value' => 10, 'max_discount_amount' => null]);
        $capped = Coupon::factory()->percentage()->create(['discount_value' => 50, 'max_discount_amount' => 100]);

        $this->assertSame(50.0, $service->calculateDiscount($fixed, $cart));
        $this->assertSame(34.0, $service->calculateDiscount($percentage, $cart));
        $this->assertSame(100.0, $service->calculateDiscount($capped, $cart));
    }

    public function test_coupon_validation_rejects_inactive_expired_upcoming_and_minimum_order(): void
    {
        [$cart] = $this->cartWithItem();
        $service = app(CouponService::class);

        foreach ([
            Coupon::factory()->create(['code' => 'INACTIVE', 'status' => false]),
            Coupon::factory()->create(['code' => 'EXPIRED', 'expires_at' => now()->subDay()]),
            Coupon::factory()->create(['code' => 'UPCOMING', 'starts_at' => now()->addDay()]),
            Coupon::factory()->create(['code' => 'MINIMUM', 'minimum_order_amount' => 999]),
        ] as $coupon) {
            try {
                $service->validateCouponForCart($cart, $coupon->code);
                $this->fail('Coupon validation should have failed.');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_customer_specific_and_usage_limits_are_enforced(): void
    {
        [$cart] = $this->cartWithItem();
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $service = app(CouponService::class);

        $specific = Coupon::factory()->create(['customer_id' => $customer->id]);
        $this->expectException(\InvalidArgumentException::class);
        $service->validateCouponForCart($cart, $specific->code, $otherCustomer);
    }

    public function test_usage_limits_total_customer_and_session_are_enforced(): void
    {
        [$cart] = $this->cartWithItem();
        $customer = Customer::factory()->create();
        $service = app(CouponService::class);

        $total = Coupon::factory()->create(['usage_limit_total' => 1]);
        CouponUsage::factory()->create(['coupon_id' => $total->id]);
        $this->assertCouponFails(fn () => $service->validateCouponForCart($cart, $total->code));

        $perCustomer = Coupon::factory()->create(['usage_limit_per_customer' => 1]);
        CouponUsage::factory()->create(['coupon_id' => $perCustomer->id, 'customer_id' => $customer->id]);
        $this->assertCouponFails(fn () => $service->validateCouponForCart($cart, $perCustomer->code, $customer));

        $perSession = Coupon::factory()->create(['usage_limit_per_session' => 1]);
        CouponUsage::factory()->create(['coupon_id' => $perSession->id, 'session_id' => $cart->session_id]);
        $this->assertCouponFails(fn () => $service->validateCouponForCart($cart, $perSession->code));
    }

    public function test_apply_and_remove_coupon_from_cart(): void
    {
        [, $variant] = $this->cartWithItem();
        $coupon = Coupon::factory()->create(['code' => 'GROCERY50', 'discount_value' => 50]);

        $this->post(route('cart.coupon.apply'), ['code' => 'grocery50'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('carts', [
            'coupon_id' => $coupon->id,
            'coupon_code' => 'GROCERY50',
            'coupon_discount_amount' => 50,
        ]);

        $this->get(route('cart.show'))->assertOk()->assertSee('GROCERY50');

        $this->delete(route('cart.coupon.remove'))->assertRedirect();
        $this->assertDatabaseMissing('carts', ['coupon_code' => 'GROCERY50']);
        $this->assertSame($variant->id, CartItem::query()->firstOrFail()->product_variant_id);
    }

    public function test_checkout_revalidates_coupon_creates_usage_and_payment_uses_discounted_total(): void
    {
        $this->cartWithItem(quantity: 2);
        Coupon::factory()->create(['code' => 'SAVE20', 'discount_type' => 'fixed', 'discount_value' => 20]);
        $this->post(route('cart.coupon.apply'), ['code' => 'SAVE20']);

        $this->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->assertSame('SAVE20', $order->coupon_code_snapshot);
        $this->assertSame('20.00', $order->coupon_discount_amount);
        $this->assertSame('116.00', $order->grand_total);
        $this->assertSame($order->grand_total, $payment->amount);
        $this->assertDatabaseHas('coupon_usages', [
            'order_id' => $order->id,
            'code_snapshot' => 'SAVE20',
            'discount_amount' => 20,
        ]);
    }

    public function test_failed_order_does_not_create_coupon_usage(): void
    {
        [, , $inventory] = $this->cartWithItem();
        Coupon::factory()->create(['code' => 'SAVE20', 'discount_value' => 20]);
        $this->post(route('cart.coupon.apply'), ['code' => 'SAVE20']);
        $inventory->update(['quantity_on_hand' => 0]);

        $this->post(route('checkout.place'), $this->checkoutPayload())->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, CouponUsage::query()->count());
    }

    public function test_coupon_works_with_logged_in_customer(): void
    {
        $customer = Customer::factory()->create(['mobile' => '9876543210']);
        $this->withSession(['customer_id' => $customer->id])->cartWithItem();
        Coupon::factory()->create(['code' => 'CUSTOMER50', 'customer_id' => $customer->id, 'discount_value' => 50]);

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.coupon.apply'), ['code' => 'CUSTOMER50'])->assertSessionHasNoErrors();
        $this->withSession(['customer_id' => $customer->id])->post(route('checkout.place'), $this->checkoutPayload())->assertRedirect();

        $this->assertDatabaseHas('coupon_usages', ['customer_id' => $customer->id, 'code_snapshot' => 'CUSTOMER50']);
    }

    public function test_admin_coupon_routes_require_authorization_and_no_disallowed_modules_or_stock_fields(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);
        $this->actingAs($user)->get(route('admin.coupons.index'))->assertForbidden();

        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }

        $this->assertFalse(class_exists('App\\Models\\Wallet'));
    }

    private function assertCouponFails(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Coupon validation should have failed.');
        } catch (\InvalidArgumentException) {
            $this->assertTrue(true);
        }
    }

    private function couponPayload(array $overrides = []): array
    {
        return array_merge([
            'code' => 'GROCERY50',
            'name' => 'Grocery discount',
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'minimum_order_amount' => 0,
            'status' => 1,
            'source' => 'admin',
        ], $overrides);
    }

    private function cartWithItem(float $quantity = 1): array
    {
        $product = Product::factory()->create(['status' => true, 'hsn_code' => '1101', 'gst_rate' => 5]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
            'sku' => fake()->unique()->bothify('GK-ATTA-####'),
            'mrp' => 75,
            'selling_price' => 68,
            'status' => true,
        ]);
        $product->update(['default_variant_id' => $variant->id]);
        $inventory = Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        $this->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => $quantity]);
        $cart = CartItem::query()->firstOrFail()->cart()->with('items')->firstOrFail();

        return [$cart, $variant, $inventory];
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
            'delivery_slot' => '9-11 AM',
            'payment_method' => 'cod',
        ];
    }
}
