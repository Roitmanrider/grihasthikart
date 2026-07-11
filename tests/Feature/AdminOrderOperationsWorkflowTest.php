<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderOperationsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
    }

    public function test_admin_can_confirm_order(): void
    {
        $order = Order::factory()->create(['order_status' => 'placed']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'confirmed'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('confirmed', $order->fresh()->order_status);
    }

    public function test_admin_can_move_confirmed_to_picking(): void
    {
        $order = Order::factory()->create(['order_status' => 'confirmed']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'picking'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('picking', $order->fresh()->order_status);
    }

    public function test_admin_can_mark_picking_to_packed(): void
    {
        $order = Order::factory()->create(['order_status' => 'picking']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'packed'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('packed', $order->fresh()->order_status);
    }

    public function test_admin_can_mark_packed_to_out_for_delivery(): void
    {
        $order = Order::factory()->create(['order_status' => 'packed']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'out_for_delivery'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('out_for_delivery', $order->fresh()->order_status);
    }

    public function test_admin_can_mark_out_for_delivery_to_delivered(): void
    {
        $order = Order::factory()->create(['order_status' => 'out_for_delivery']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'delivered'])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('delivered', $order->fresh()->order_status);
        $this->assertNotNull($order->fresh()->delivered_at);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $order = Order::factory()->create(['order_status' => 'placed']);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'delivered'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame('placed', $order->fresh()->order_status);
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->create(['order_status' => 'delivered']);

        $this->actingAs($this->admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.update-status', $order), ['order_status' => 'cancelled_by_admin'])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHasErrors('order');

        $this->assertSame('delivered', $order->fresh()->order_status);
    }

    public function test_admin_cancel_stores_reason_and_displays_it(): void
    {
        $order = Order::factory()->create(['order_status' => 'confirmed']);

        $this->actingAs($this->admin)
            ->patch(route('admin.orders.update-status', $order), [
                'order_status' => 'cancelled_by_admin',
                'admin_notes' => 'Stock unavailable',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('cancelled_by_admin', $order->fresh()->order_status);
        $this->assertSame('Stock unavailable', $order->fresh()->admin_notes);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Cancellation Reason')
            ->assertSee('Stock unavailable');
    }

    public function test_admin_order_page_shows_document_buttons_and_valid_actions(): void
    {
        $order = $this->orderWithItem(['order_status' => 'confirmed']);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Invoice')
            ->assertSee('Picking Slip')
            ->assertSee('Packing Slip')
            ->assertSee('Start Picking')
            ->assertSee('Cancel Order')
            ->assertDontSee('Mark Delivered');
    }

    public function test_customer_order_page_shows_status_timeline_read_only(): void
    {
        $customer = Customer::factory()->create();
        $order = $this->orderWithItem([
            'customer_id' => $customer->id,
            'order_status' => 'packed',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $order->order_number))
            ->assertOk()
            ->assertSee('Order Timeline')
            ->assertSee('Packed')
            ->assertDontSee('Cancel Order');
    }

    private function orderWithItem(array $orderOverrides = []): Order
    {
        $order = Order::factory()->create($orderOverrides);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_name_snapshot' => 'Wheat Atta',
            'variant_name_snapshot' => '1kg',
            'sku_snapshot' => 'GK-ATTA-1KG',
            'quantity' => 2,
            'unit_price' => 100,
            'line_total' => 200,
        ]);

        return $order;
    }
}
