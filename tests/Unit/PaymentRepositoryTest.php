<?php

namespace Tests\Unit;

use App\Domains\Payment\Repositories\PaymentRepository;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_payments_by_search_method_status_and_date(): void
    {
        $order = Order::factory()->create([
            'order_number' => 'GK-PAYMENT',
            'customer_name' => 'Rohit Kumar',
            'customer_mobile' => '9876543210',
        ]);
        $matched = Payment::factory()->create([
            'order_id' => $order->id,
            'payment_number' => 'PAY-MATCH',
            'payment_method' => 'qr',
            'payment_status' => 'awaiting_verification',
        ]);
        Payment::factory()->create(['payment_method' => 'cod']);

        $repository = new PaymentRepository(new Payment);
        $payments = $repository->paginatedList([
            'search' => 'Rohit',
            'payment_method' => 'qr',
            'payment_status' => 'awaiting_verification',
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]);

        $this->assertTrue($payments->getCollection()->contains('id', $matched->id));
        $this->assertCount(1, $payments->getCollection());
    }

    public function test_it_finds_active_payment_for_order(): void
    {
        $order = Order::factory()->create();
        $payment = Payment::factory()->create(['order_id' => $order->id]);

        $repository = new PaymentRepository(new Payment);

        $this->assertSame($payment->id, $repository->activeForOrder($order)->id);
    }
}
