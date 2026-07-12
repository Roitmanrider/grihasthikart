<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestItem;
use App\Models\StockLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private StockLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        config(['grihasthikart.admin_emails' => ['admin@example.com']]);
        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->location = StockLocation::factory()->create([
            'name' => 'Main Store',
            'code' => 'MAIN',
            'is_default' => true,
            'status' => true,
        ]);
    }

    public function test_return_button_visible_only_for_eligible_delivered_order(): void
    {
        [$customer, $delivered] = $this->deliveredOrder();
        $placed = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => 'placed',
            'delivered_at' => null,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $delivered->order_number))
            ->assertOk()
            ->assertSee('Request Return');

        $this->withSession(['customer_id' => $customer->id])
            ->get(route('customer.orders.show', $placed->order_number))
            ->assertOk()
            ->assertDontSee('Request Return');
    }

    public function test_customer_can_create_valid_return_request(): void
    {
        [$customer, $order, $item] = $this->deliveredOrder();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.returns.store'), $this->payload($order, $item, 2))
            ->assertRedirect();

        $return = ReturnRequest::query()->firstOrFail();
        $returnItem = ReturnRequestItem::query()->firstOrFail();

        $this->assertSame('requested', $return->status);
        $this->assertSame('100.00', $return->refund_amount);
        $this->assertSame($item->id, $returnItem->order_item_id);
        $this->assertSame('2.000', $returnItem->quantity);
        $this->assertSame('50.00', $returnItem->unit_price);
        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_ADMIN,
            'type' => 'return.requested',
        ]);
    }

    public function test_customer_cannot_return_non_delivered_order(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'order_status' => 'confirmed',
            'delivered_at' => null,
        ]);
        $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1, 'unit_price' => 50]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.returns.store'), $this->payload($order, $item, 1))
            ->assertSessionHasErrors('return');

        $this->assertSame(0, ReturnRequest::query()->count());
    }

    public function test_return_window_enforced(): void
    {
        [$customer, $order, $item] = $this->deliveredOrder(['delivered_at' => now()->subDays(4)]);

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.returns.store'), $this->payload($order, $item, 1))
            ->assertSessionHasErrors('return');

        $this->assertSame(0, ReturnRequest::query()->count());
    }

    public function test_cannot_return_more_than_purchased_quantity(): void
    {
        [$customer, $order, $item] = $this->deliveredOrder();

        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.returns.store'), $this->payload($order, $item, 4))
            ->assertSessionHasErrors('return');

        $this->assertSame(0, ReturnRequest::query()->count());
    }

    public function test_admin_can_approve_return(): void
    {
        $return = $this->returnRequest();

        $this->actingAs($this->admin)
            ->patch(route('admin.returns.approve', $return), [
                'admin_notes' => 'Approved',
                'restock_items' => 0,
            ])
            ->assertRedirect();

        $this->assertSame('approved', $return->fresh()->status);
        $this->assertNotNull($return->fresh()->approved_at);
        $this->assertDatabaseHas('notifications', [
            'audience' => Notification::AUDIENCE_CUSTOMER,
            'type' => 'return.updated',
            'customer_id' => $return->customer_id,
        ]);
    }

    public function test_admin_can_reject_return_with_reason(): void
    {
        $return = $this->returnRequest();

        $this->actingAs($this->admin)
            ->patch(route('admin.returns.reject', $return), ['admin_notes' => 'Opened product'])
            ->assertRedirect();

        $this->assertSame('rejected', $return->fresh()->status);
        $this->assertSame('Opened product', $return->fresh()->admin_notes);
    }

    public function test_admin_mark_refunded(): void
    {
        $return = $this->returnRequest();
        $return->update(['status' => 'approved', 'approved_at' => now()]);

        $this->actingAs($this->admin)
            ->patch(route('admin.returns.mark-refunded', $return), ['admin_notes' => 'Manual refund done'])
            ->assertRedirect();

        $this->assertSame('refunded', $return->fresh()->status);
        $this->assertSame('Manual refund done', $return->fresh()->admin_notes);
    }

    public function test_restock_approval_increases_inventory(): void
    {
        [$customer, $order, $item, $inventory] = $this->deliveredOrder(inventoryOverrides: ['quantity_on_hand' => 1]);
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.returns.store'), $this->payload($order, $item, 2));
        $return = ReturnRequest::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->patch(route('admin.returns.approve', $return), [
                'admin_notes' => 'Restock good items',
                'restock_items' => 1,
            ])
            ->assertRedirect();

        $this->assertSame('3.000', $inventory->fresh()->quantity_on_hand);
        $movement = InventoryMovement::query()->firstOrFail();
        $this->assertSame('return_in', $movement->movement_type);
        $this->assertSame(ReturnRequest::class, $movement->reference_type);
        $this->assertSame($return->id, $movement->reference_id);
    }

    public function test_customer_cannot_view_another_customer_return(): void
    {
        $return = $this->returnRequest();
        $other = Customer::factory()->create();

        $this->withSession(['customer_id' => $other->id])
            ->get(route('customer.returns.show', $return))
            ->assertNotFound();
    }

    public function test_guest_blocked(): void
    {
        $return = $this->returnRequest();
        $this->flushSession();

        $this->get(route('customer.returns.index'))->assertRedirect(route('customer.login'));
        $this->get(route('admin.returns.index'))->assertRedirect(route('login'));
        $this->get(route('customer.returns.show', $return))->assertRedirect(route('customer.login'));
    }

    private function payload(Order $order, OrderItem $item, float $quantity): array
    {
        return [
            'order_id' => $order->id,
            'reason' => 'Damaged item',
            'customer_notes' => 'Please refund',
            'items' => [
                [
                    'order_item_id' => $item->id,
                    'quantity' => $quantity,
                    'reason' => 'Damaged',
                    'condition' => 'Sealed',
                ],
            ],
        ];
    }

    private function returnRequest(): ReturnRequest
    {
        [$customer, $order, $item] = $this->deliveredOrder();
        $this->withSession(['customer_id' => $customer->id])
            ->post(route('customer.returns.store'), $this->payload($order, $item, 1));

        return ReturnRequest::query()->firstOrFail();
    }

    private function deliveredOrder(array $orderOverrides = [], array $itemOverrides = [], array $inventoryOverrides = []): array
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['status' => true]);
        $variant = ProductVariant::factory()->default()->create(['product_id' => $product->id, 'status' => true]);
        $product->update(['default_variant_id' => $variant->id]);
        $inventory = Inventory::factory()->create(array_merge([
            'product_variant_id' => $variant->id,
            'stock_location_id' => $this->location->id,
            'quantity_on_hand' => 0,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'status' => true,
        ], $inventoryOverrides));
        $order = Order::factory()->create(array_merge([
            'customer_id' => $customer->id,
            'order_status' => 'delivered',
            'delivered_at' => now()->subDay(),
        ], $orderOverrides));
        $item = OrderItem::factory()->create(array_merge([
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'unit_price' => 50,
            'line_total' => 150,
        ], $itemOverrides));

        return [$customer, $order, $item, $inventory];
    }
}
