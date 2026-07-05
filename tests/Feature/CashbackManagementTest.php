<?php

namespace Tests\Feature;

use App\Domains\Cashback\Services\CashbackCalculationService;
use App\Domains\Cashback\Services\CashbackService;
use App\Models\CashbackLedger;
use App\Models\CashbackRedemptionRequest;
use App\Models\CashbackRule;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CashbackManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        CashbackRule::factory()->create(['is_default' => true]);
    }

    public function test_admin_can_create_update_rule_and_only_one_default_is_active(): void
    {
        $this->actingAs($this->admin)->post(route('admin.cashback.rules.store'), $this->rulePayload([
            'name' => 'Premium Rule',
            'is_default' => 1,
        ]))->assertRedirect(route('admin.cashback.rules.index'));

        $rule = CashbackRule::query()->where('name', 'Premium Rule')->firstOrFail();
        $this->assertTrue($rule->is_default);
        $this->assertSame(1, CashbackRule::query()->where('is_default', true)->count());

        $this->actingAs($this->admin)->patch(route('admin.cashback.rules.update', $rule), $this->rulePayload([
            'name' => 'Premium Rule Updated',
            'monthly_order_threshold' => 10000,
            'is_default' => 1,
        ]))->assertRedirect(route('admin.cashback.rules.index'));

        $this->assertSame('10000.00', $rule->fresh()->monthly_order_threshold);
    }

    public function test_delivered_logged_in_order_earns_cashback_after_delay_once(): void
    {
        $customer = Customer::factory()->create(['cashback_enabled' => true]);
        $order = $this->eligibleOrder($customer, subtotal: 5000);

        $created = app(CashbackCalculationService::class)->processEligibleCashbackForMonth($customer, (int) $order->delivered_at->year, (int) $order->delivered_at->month);

        $this->assertSame(1, $created);
        $this->assertDatabaseHas('cashback_ledgers', [
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'ledger_type' => 'earned',
            'amount' => 250,
        ]);
        $this->assertSame(0, app(CashbackCalculationService::class)->processEligibleCashbackForMonth($customer, (int) $order->delivered_at->year, (int) $order->delivered_at->month));
        $this->assertSame(1, CashbackLedger::query()->where('order_id', $order->id)->where('ledger_type', 'earned')->count());
    }

    public function test_guest_non_delivered_cancelled_and_recent_orders_are_not_eligible(): void
    {
        $customer = Customer::factory()->create(['cashback_enabled' => true]);
        $guest = $this->eligibleOrder(null, subtotal: 5000);
        $pending = $this->eligibleOrder($customer, subtotal: 5000, overrides: ['order_status' => 'placed']);
        $cancelled = $this->eligibleOrder($customer, subtotal: 5000, overrides: ['order_status' => 'cancelled']);
        $recent = $this->eligibleOrder($customer, subtotal: 5000, overrides: ['delivered_at' => now()->subDay()]);

        $created = app(CashbackCalculationService::class)->processEligibleCashbackForMonth($customer, (int) $recent->delivered_at->year, (int) $recent->delivered_at->month);

        $this->assertSame(0, $created);
        $this->assertSame(0, CashbackLedger::query()->count());
        $this->assertNull($guest->customer_id);
        $this->assertSame('placed', $pending->order_status);
        $this->assertSame('cancelled', $cancelled->order_status);
    }

    public function test_monthly_threshold_category_threshold_and_coupon_discount_exclusion(): void
    {
        $customer = Customer::factory()->create(['cashback_enabled' => true]);
        $belowThreshold = $this->eligibleOrder($customer, subtotal: 4000);
        app(CashbackCalculationService::class)->processEligibleCashbackForMonth($customer, (int) $belowThreshold->delivered_at->year, (int) $belowThreshold->delivered_at->month);
        $this->assertSame(0, CashbackLedger::query()->count());

        CashbackLedger::query()->delete();
        $otherCustomer = Customer::factory()->create(['cashback_enabled' => true]);
        $belowCategory = $this->eligibleOrder($otherCustomer, subtotal: 5000, eligibleCategory: false);
        app(CashbackCalculationService::class)->processEligibleCashbackForMonth($otherCustomer, (int) $belowCategory->delivered_at->year, (int) $belowCategory->delivered_at->month);
        $this->assertSame(0, CashbackLedger::query()->count());

        $discountCustomer = Customer::factory()->create(['cashback_enabled' => true]);
        $discounted = $this->eligibleOrder($discountCustomer, subtotal: 5000, overrides: ['discount_total' => 500]);
        app(CashbackCalculationService::class)->processEligibleCashbackForMonth($discountCustomer, (int) $discounted->delivered_at->year, (int) $discounted->delivered_at->month);
        $this->assertDatabaseHas('cashback_ledgers', ['customer_id' => $discountCustomer->id, 'amount' => 225]);
    }

    public function test_customer_balance_and_redemption_validation(): void
    {
        $customer = Customer::factory()->create(['cashback_enabled' => true]);
        app(CashbackService::class)->writeLedger($customer, 'earned', 1000);

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.cashback.redeem'), [
            'requested_amount' => 499,
        ])->assertSessionHasErrors('cashback');

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.cashback.redeem'), [
            'requested_amount' => 750,
        ])->assertSessionHasErrors('cashback');

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.cashback.redeem'), [
            'requested_amount' => 1500,
        ])->assertSessionHasErrors('cashback');

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.cashback.redeem'), [
            'requested_amount' => 500,
        ])->assertRedirect();

        $this->withSession(['customer_id' => $customer->id])->post(route('customer.cashback.redeem'), [
            'requested_amount' => 1000,
        ])->assertSessionHasErrors('cashback');
    }

    public function test_admin_can_approve_reject_and_generate_cashback_coupon(): void
    {
        $customer = Customer::factory()->create(['cashback_enabled' => true]);
        app(CashbackService::class)->writeLedger($customer, 'earned', 1000);
        $request = CashbackRedemptionRequest::factory()->create(['customer_id' => $customer->id, 'requested_amount' => 500]);

        $this->actingAs($this->admin)->patch(route('admin.cashback.redemptions.approve', $request), [
            'approved_amount' => 500,
            'admin_note' => 'Approved',
        ])->assertRedirect();

        $this->actingAs($this->admin)->post(route('admin.cashback.redemptions.generate-coupon', $request))->assertRedirect();

        $coupon = Coupon::query()->where('source', 'cashback')->firstOrFail();
        $this->assertSame('fixed', $coupon->discount_type);
        $this->assertSame($customer->id, $coupon->customer_id);
        $this->assertTrue($coupon->is_cashback_coupon);
        $this->assertDatabaseHas('cashback_ledgers', [
            'customer_id' => $customer->id,
            'coupon_id' => $coupon->id,
            'ledger_type' => 'redeemed',
            'amount' => 500,
        ]);

        $rejected = CashbackRedemptionRequest::factory()->create(['customer_id' => $customer->id, 'requested_amount' => 500]);
        $this->actingAs($this->admin)->patch(route('admin.cashback.redemptions.reject', $rejected), [
            'admin_note' => 'Not valid',
        ])->assertRedirect();
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_customer_sees_cashback_coupon_and_existing_coupon_flow_accepts_it(): void
    {
        $customer = Customer::factory()->create(['cashback_enabled' => true]);
        $coupon = Coupon::factory()->create([
            'code' => 'GKCB-TEST',
            'customer_id' => $customer->id,
            'discount_type' => 'fixed',
            'discount_value' => 500,
            'source' => 'cashback',
            'is_cashback_coupon' => true,
        ]);
        $this->cartWithItem($customer);

        $this->withSession(['customer_id' => $customer->id])->get(route('customer.cashback.index'))
            ->assertOk()
            ->assertSee('GKCB-TEST');

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.coupon.apply'), ['code' => $coupon->code])
            ->assertSessionHasNoErrors();
    }

    public function test_customer_and_admin_cashback_authorization_and_no_disallowed_modules(): void
    {
        $customer = Customer::factory()->create();
        $other = Customer::factory()->create();
        app(CashbackService::class)->writeLedger($customer, 'earned', 500);

        $this->withSession(['customer_id' => $other->id])->get(route('customer.cashback.index'))
            ->assertOk()
            ->assertSee('Rs. 0.00');

        $user = User::factory()->create(['email' => 'customer@example.com']);
        $this->actingAs($user)->get(route('admin.cashback.index'))->assertForbidden();

        $this->assertFalse(class_exists('App\\Models\\Wallet'));
        foreach (['stock_quantity', 'reserved_quantity', 'available_quantity', 'quantity_on_hand'] as $column) {
            $this->assertFalse(Schema::hasColumn('products', $column));
            $this->assertFalse(Schema::hasColumn('product_variants', $column));
        }
    }

    private function rulePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rule',
            'cashback_percent' => 5,
            'monthly_order_threshold' => 5000,
            'eligible_category_threshold_percent' => 50,
            'redemption_multiple' => 500,
            'processing_delay_days' => 2,
            'status' => 1,
            'is_default' => 0,
        ], $overrides);
    }

    private function eligibleOrder(?Customer $customer, float $subtotal, bool $eligibleCategory = true, array $overrides = []): Order
    {
        $category = Category::query()->firstOrCreate([
            'slug' => $eligibleCategory ? 'vegetables-fruits' : 'home-care',
        ], [
            'name' => $eligibleCategory ? 'Vegetables & Fruits' : 'Home Care',
        ]);
        $product = Product::factory()->create(['status' => true]);
        $product->categories()->attach($category->id, ['is_primary' => true, 'display_order' => 0]);

        $order = Order::factory()->create(array_merge([
            'customer_id' => $customer?->id,
            'order_status' => 'delivered',
            'payment_status' => 'paid',
            'subtotal' => $subtotal,
            'discount_total' => 0,
            'grand_total' => $subtotal,
            'delivered_at' => now()->subDays(3),
        ], $overrides));

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'line_total' => $subtotal,
            'line_subtotal' => $subtotal,
        ]);

        return $order->fresh();
    }

    private function cartWithItem(Customer $customer): void
    {
        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'selling_price' => 1000,
            'mrp' => 1000,
            'status' => true,
        ]);
        Inventory::factory()->create([
            'product_variant_id' => $variant->id,
            'quantity_on_hand' => 10,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ]);

        $this->withSession(['customer_id' => $customer->id])->post(route('cart.items.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);
    }
}
