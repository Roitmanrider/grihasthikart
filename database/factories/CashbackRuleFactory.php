<?php

namespace Database\Factories;

use App\Models\CashbackRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CashbackRule> */
class CashbackRuleFactory extends Factory
{
    protected $model = CashbackRule::class;

    public function definition(): array
    {
        return [
            'name' => 'Default Cashback Rule',
            'cashback_percent' => 5,
            'monthly_order_threshold' => 5000,
            'eligible_category_threshold_percent' => 50,
            'redemption_multiple' => 500,
            'processing_delay_days' => 2,
            'status' => true,
            'is_default' => false,
            'metadata' => null,
        ];
    }
}
