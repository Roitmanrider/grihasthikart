<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PurchaseEntry;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFinancialDisplayManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_customer_order_list_shows_persisted_financial_breakdown(): void
    {
        $customer = Customer::factory()->create(['delivery_charge_override' => 150]);
        $charged = $this->orderWithItem($customer, [
            'order_number' => 'GK-FIN-1001',
            'subtotal' => 500,
            'delivery_charge' => 60,
            'grand_total' => 560,
        ]);
        $free = $this->orderWithItem($customer, [
            'order_number' => 'GK-FIN-1002',
            'subtotal' => 300,
            'delivery_charge' => 0,
            'grand_total' => 300,
        ]);

        $customer->update(['delivery_charge_override' => 0]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee($charged->order_number)
            ->assertSee('Rs. 500.00')
            ->assertSee('Rs. 60.00')
            ->assertSee('Rs. 560.00')
            ->assertSee($free->order_number)
            ->assertSee('Free')
            ->assertSee('Rs. 300.00');
    }

    public function test_customer_order_detail_reconciles_bill_breakdown_to_grand_total(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderWithItem($customer, [
            'order_number' => 'GK-FIN-2001',
            'subtotal' => 200,
            'total_mrp' => 250,
            'total_savings' => 50,
            'tax_total' => 10,
            'discount_total' => 20,
            'delivery_charge' => 30,
            'grand_total' => 210,
        ], [
            'product_name_snapshot' => 'Display Rice',
            'variant_name_snapshot' => '5kg',
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
            'gst_rate_snapshot' => 5,
            'tax_amount' => 10,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Display Rice')
            ->assertSee('Unit price: Rs. 100.00 / Merchandise: Rs. 200.00')
            ->assertSee('GST 5.00% / Tax: Rs. 10.00')
            ->assertSee('Merchandise Amount')
            ->assertSee('Rs. 200.00')
            ->assertSee('MRP Total')
            ->assertSee('Rs. 250.00')
            ->assertSee('Coupon Discount')
            ->assertSee('- Rs. 20.00')
            ->assertSee('Delivery Charge')
            ->assertSee('Rs. 30.00')
            ->assertSee('Final Amount')
            ->assertSee('Rs. 210.00');

        $this->assertSame(210.0, round((float) $order->subtotal - (float) $order->discount_total + (float) $order->delivery_charge, 2));
    }

    public function test_admin_order_surfaces_show_financial_composition(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderWithItem($customer, [
            'order_number' => 'GK-FIN-3001',
            'subtotal' => 420,
            'tax_total' => 20,
            'delivery_charge' => 35,
            'grand_total' => 455,
            'order_status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Rs. 420.00')
            ->assertSee('Rs. 35.00')
            ->assertSee('Rs. 455.00');

        $this->actingAs($this->admin)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Merchandise Amount')
            ->assertSee('Delivery Charge')
            ->assertSee('Rs. 420.00')
            ->assertSee('Rs. 35.00')
            ->assertSee('Rs. 455.00');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Merchandise Value')
            ->assertSee('Tax / GST')
            ->assertSee('Delivery Charge')
            ->assertSee('Rs. 35.00');
    }

    public function test_purchase_listing_shows_goods_freight_and_final_total(): void
    {
        $purchase = PurchaseEntry::query()->create([
            'supplier_id' => Supplier::factory()->create(['name' => 'Financial Supplier'])->id,
            'purchase_number' => 'PUR-FIN-1001',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 1000,
            'gst_total' => 120,
            'discount_total' => 50,
            'cgst_total' => 60,
            'sgst_total' => 60,
            'grand_total' => 1070,
            'freight_allocation' => 80,
            'status' => PurchaseEntry::STATUS_POSTED,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.purchases.index'))
            ->assertOk()
            ->assertSee($purchase->purchase_number)
            ->assertSee('Goods / Purchase Amount')
            ->assertSee('Freight')
            ->assertSee('Final Purchase Total')
            ->assertSee('Rs. 1,000.00')
            ->assertSee('Rs. 80.00')
            ->assertSee('Rs. 1,070.00');
    }

    private function orderWithItem(Customer $customer, array $orderOverrides = [], array $itemOverrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_mobile' => $customer->mobile,
            'customer_email' => $customer->email,
            'placed_at' => now(),
            'subtotal' => 100,
            'delivery_charge' => 0,
            'grand_total' => 100,
        ], $orderOverrides));

        OrderItem::factory()->create(array_merge([
            'order_id' => $order->id,
            'product_name_snapshot' => 'Display Product',
            'variant_name_snapshot' => '1kg',
            'sku_snapshot' => 'DISPLAY-SKU',
            'quantity' => 1,
            'unit_price' => (float) $order->subtotal,
            'line_subtotal' => (float) $order->subtotal,
            'line_total' => (float) $order->subtotal,
            'tax_amount' => (float) $order->tax_total,
        ], $itemOverrides));

        return $order;
    }
}
