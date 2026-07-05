<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PaymentTransaction> */
class PaymentTransactionFactory extends Factory
{
    protected $model = PaymentTransaction::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'transaction_type' => 'initiated',
            'status' => 'pending',
            'amount' => 100,
            'gateway_reference' => null,
            'payload' => null,
            'note' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
