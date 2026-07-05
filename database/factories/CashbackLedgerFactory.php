<?php

namespace Database\Factories;

use App\Models\CashbackLedger;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashbackLedger> */
class CashbackLedgerFactory extends Factory
{
    protected $model = CashbackLedger::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'ledger_type' => 'earned',
            'amount' => 500,
            'balance_after' => 500,
            'description' => fake()->sentence(),
            'metadata' => null,
            'created_by' => null,
        ];
    }
}
