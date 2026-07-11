<?php

namespace Tests\Feature;

use App\Models\BusinessSetting;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class OrderDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_view_invoice(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Tax Invoice')
            ->assertSee('INV-'.$order->order_number)
            ->assertSee($order->customer_name)
            ->assertSee('Grand Total')
            ->assertSee('Rs. 220.00');
    }

    public function test_admin_can_view_picking_slip_without_prices(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.picking-slip', $order))
            ->assertOk()
            ->assertSee('Picking Slip')
            ->assertSee('GK-ATTA-1KG')
            ->assertSee('Wheat Atta')
            ->assertDontSee('Rs.')
            ->assertDontSee('Grand Total');
    }

    public function test_admin_can_view_packing_slip_without_internal_costs(): void
    {
        $order = $this->orderWithItem();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.packing-slip', $order))
            ->assertOk()
            ->assertSee('Packing Slip')
            ->assertSee($order->customer_name)
            ->assertSee('Wheat Atta')
            ->assertDontSee('purchase')
            ->assertDontSee('profit')
            ->assertDontSee('Rs.');
    }

    public function test_guest_cannot_view_admin_documents(): void
    {
        $order = $this->orderWithItem();

        $this->get(route('admin.orders.invoice', $order))->assertRedirect(route('admin.login'));
        $this->get(route('admin.orders.picking-slip', $order))->assertRedirect(route('admin.login'));
        $this->get(route('admin.orders.packing-slip', $order))->assertRedirect(route('admin.login'));
    }

    public function test_customer_can_view_own_invoice(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderWithItem(['customer_id' => $customer->id]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.invoice', $order->order_number))
            ->assertOk()
            ->assertSee('Tax Invoice')
            ->assertSee($order->order_number)
            ->assertSee($order->customer_name)
            ->assertSee('Rs. 220.00');
    }

    public function test_customer_invoice_visibility_follows_order_setting(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderWithItem(['customer_id' => $customer->id]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('View/Print Invoice');

        BusinessSetting::query()->updateOrCreate(
            ['group' => 'order', 'key' => 'customer_invoice_enabled'],
            ['value' => '0', 'value_type' => 'boolean', 'label' => 'Customer Invoice Printing Enabled']
        );
        Cache::forget('business_setting_order.customer_invoice_enabled');

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertDontSee('View/Print Invoice');

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.invoice', $order->order_number))
            ->assertForbidden()
            ->assertSee('Customer invoice printing is currently disabled.');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Tax Invoice');
    }

    public function test_customer_cannot_view_another_customer_invoice(): void
    {
        $customer = Customer::factory()->create();
        $otherCustomer = Customer::factory()->create();
        $order = $this->orderWithItem(['customer_id' => $otherCustomer->id]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.invoice', $order->order_number))
            ->assertNotFound();
    }

    public function test_guest_cannot_view_customer_invoice(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderWithItem(['customer_id' => $customer->id]);

        $this->get(route('customer.orders.invoice', $order->order_number))
            ->assertRedirect(route('customer.login'));
    }

    private function orderWithItem(array $orderOverrides = []): Order
    {
        $order = Order::factory()->create(array_merge([
            'order_number' => 'GK-DOC-1001',
            'customer_name' => 'Rohit Sharma',
            'customer_mobile' => '9876543210',
            'delivery_address_line1' => '221 Green Street',
            'delivery_city' => 'Kolkata',
            'delivery_state' => 'West Bengal',
            'delivery_pincode' => '700001',
            'delivery_slot' => '9-11 AM',
            'subtotal' => 200,
            'total_mrp' => 240,
            'total_savings' => 40,
            'tax_total' => 9.52,
            'delivery_charge' => 20,
            'discount_total' => 0,
            'grand_total' => 220,
        ], $orderOverrides));

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name_snapshot' => 'Wheat Atta',
            'variant_name_snapshot' => '1kg',
            'sku_snapshot' => 'GK-ATTA-1KG',
            'hsn_code_snapshot' => '1101',
            'gst_rate_snapshot' => 5,
            'quantity' => 2,
            'mrp' => 120,
            'unit_price' => 100,
            'line_subtotal' => 200,
            'line_mrp_total' => 240,
            'line_savings' => 40,
            'tax_amount' => 9.52,
            'line_total' => 200,
        ]);

        return $order;
    }
}
