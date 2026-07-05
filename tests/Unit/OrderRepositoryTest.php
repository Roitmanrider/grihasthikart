<?php

namespace Tests\Unit;

use App\Domains\Order\Repositories\OrderRepository;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_orders_by_search_status_payment_and_date(): void
    {
        $matched = Order::factory()->create([
            'order_number' => 'GK-MATCH',
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
            'order_status' => 'placed',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'placed_at' => now(),
        ]);
        Order::factory()->create(['order_status' => 'delivered']);

        $repository = new OrderRepository(new Order);
        $orders = $repository->paginatedList([
            'search' => 'Rohit',
            'order_status' => 'placed',
            'payment_status' => 'pending',
            'payment_method' => 'cod',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]);

        $this->assertTrue($orders->getCollection()->contains('id', $matched->id));
        $this->assertCount(1, $orders->getCollection());
    }

    public function test_it_finds_order_by_number_for_session(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'GK-SESSION',
            'session_id' => 'session-a',
        ]);

        $repository = new OrderRepository(new Order);

        $this->assertSame($order->id, $repository->findByOrderNumberForSession('GK-SESSION', 'session-a')->id);
    }
}
