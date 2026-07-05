<?php

namespace Database\Factories;

use App\Models\CashbackMonthlySummary;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashbackMonthlySummary> */
class CashbackMonthlySummaryFactory extends Factory
{
    protected $model = CashbackMonthlySummary::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'year' => now()->year,
            'month' => now()->month,
            'total_delivered_order_amount' => 5000,
            'eligible_category_order_amount' => 2500,
            'coupon_discount_excluded_amount' => 0,
            'eligible_cashback_base' => 5000,
            'cashback_percent' => 5,
            'cashback_amount' => 250,
            'eligibility_status' => 'processed',
            'processed_at' => now(),
        ];
    }
}
