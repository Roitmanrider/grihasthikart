<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payment> */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_number' => 'PAY'.now()->format('ymd').fake()->unique()->bothify('????##'),
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'amount' => 100,
            'currency' => 'INR',
            'gateway' => 'cod',
            'metadata' => null,
        ];
    }
}
