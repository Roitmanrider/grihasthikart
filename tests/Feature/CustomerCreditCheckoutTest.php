<?php

namespace Tests\Feature;

use App\Domains\Setting\Services\BusinessSettingService;
use App\Domains\Storefront\Services\StorefrontAccessService;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerCreditTransaction;
use App\Models\DeliverySlot;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\BusinessSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCreditCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->seed(BusinessSettingSeeder::class);
        app(BusinessSettingService::class)->set('storefront.access_mode', StorefrontAccessService::PUBLIC_STOREFRONT);
        app(BusinessSettingService::class)->set('storefront.allow_guest_checkout', true);
        app(BusinessSettingService::class)->set('checkout.delivery_charge', 25)->update(['value_type' => 'decimal']);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        DeliverySlot::factory()->create([
            'name' => '9-11 AM',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'display_label' => '9 AM - 11 AM',
            'status' => true,
        ]);
    }

    public function test_partial_customer_credit_reduces_external_payment_and_debits_ledger(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        $this->credit($customer, 100);
        [, $variant] = $this->cartItem();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address, [
                'use_customer_credit' => 1,
                'customer_credit_amount' => 30,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();
        $payment = Payment::query()->firstOrFail();

        $this->assertSame('93.00', $order->amount_before_customer_credit);
        $this->assertSame('30.00', $order->customer_credit_used);
        $this->assertSame('63.00', $order->grand_total);
        $this->assertSame('63.00', $payment->amount);
        $this->assertDatabaseHas('customer_credit_transactions', [
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'type' => CustomerCreditTransaction::ORDER_REDEMPTION_DEBIT,
            'amount' => '30.00',
            'balance_after' => '70.00',
        ]);
    }

    public function test_full_customer_credit_creates_zero_payable_order_without_payment_record(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        $this->credit($customer, 100);
        [, $variant] = $this->cartItem();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address, [
                'use_customer_credit' => 1,
                'customer_credit_amount' => 93,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $this->assertSame('customer_credit', $order->payment_method);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('93.00', $order->customer_credit_used);
        $this->assertSame('0.00', $order->grand_total);
        $this->assertSame(0, Payment::query()->count());
    }

    public function test_customer_credit_cannot_exceed_balance_or_payable(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        $this->credit($customer, 50);
        [, $variant] = $this->cartItem();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address, [
                'use_customer_credit' => 1,
                'customer_credit_amount' => 94,
            ]))
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::query()->count());
        $this->assertSame(0, CustomerCreditTransaction::query()->where('type', CustomerCreditTransaction::ORDER_REDEMPTION_DEBIT)->count());
    }

    public function test_cancelling_order_restores_customer_credit_once(): void
    {
        [$customer, $address] = $this->customerWithAddress();
        $this->credit($customer, 100);
        [, $variant] = $this->cartItem();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('checkout.place'), $this->checkoutPayload($customer, $address, [
                'use_customer_credit' => 1,
                'customer_credit_amount' => 30,
            ]));

        $order = Order::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), [
                'order_status' => 'cancelled',
                'admin_notes' => 'Cancellation test',
            ])
            ->assertRedirect();
        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order->fresh()), [
                'order_status' => 'cancelled',
                'admin_notes' => 'Cancellation test again',
            ])
            ->assertSessionHasErrors('order');

        $this->assertSame(1, CustomerCreditTransaction::query()->where('type', CustomerCreditTransaction::ORDER_CANCELLATION_CREDIT)->count());
        $this->assertDatabaseHas('customer_credit_transactions', [
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'type' => CustomerCreditTransaction::ORDER_CANCELLATION_CREDIT,
            'amount' => '30.00',
            'balance_after' => '100.00',
        ]);
    }

    public function test_customer_credit_toggle_can_be_disabled_without_hiding_balance(): void
    {
        [$customer] = $this->customerWithAddress();
        $this->credit($customer, 80);
        [, $variant] = $this->cartItem();
        app(BusinessSettingService::class)->set('checkout.customer_credit_redemption_enabled', false)->update(['value_type' => 'boolean']);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('cart.items.store'), ['product_variant_id' => $variant->id, 'quantity' => 1]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('checkout.show'))
            ->assertOk()
            ->assertSee('Available Customer Credit: Rs. 80.00')
            ->assertSee('Customer Credit redemption is currently disabled for checkout.');
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

    private function credit(Customer $customer, float $amount): void
    {
        CustomerCreditTransaction::query()->create([
            'customer_id' => $customer->id,
            'type' => CustomerCreditTransaction::MANUAL_CREDIT,
            'amount' => $amount,
            'balance_after' => $amount,
            'source' => 'test_credit',
            'description' => 'Test Customer Credit',
        ]);
    }

    private function cartItem(): array
    {
        $product = Product::factory()->create([
            'name' => 'Wheat Atta',
            'status' => true,
            'hsn_code' => '1101',
            'gst_rate' => 5,
        ]);
        $variant = ProductVariant::factory()->default()->create([
            'product_id' => $product->id,
            'variant_name' => '1kg',
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

        return [$product, $variant, $inventory];
    }

    private function checkoutPayload(Customer $customer, CustomerAddress $address, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => $customer->name,
            'customer_mobile' => $customer->mobile,
            'customer_email' => $customer->email,
            'customer_address_id' => $address->id,
            'delivery_date' => now()->addDay()->toDateString(),
            'delivery_slot' => '9 AM - 11 AM',
            'payment_method' => 'cod',
            'notes' => null,
        ], $overrides);
    }
}
