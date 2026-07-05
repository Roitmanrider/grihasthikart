<?php

namespace Database\Factories;

use App\Models\CashbackRedemptionRequest;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashbackRedemptionRequest> */
class CashbackRedemptionRequestFactory extends Factory
{
    protected $model = CashbackRedemptionRequest::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'requested_amount' => 500,
            'approved_amount' => null,
            'status' => 'pending',
            'customer_note' => null,
            'admin_note' => null,
            'requested_at' => now(),
        ];
    }
}
