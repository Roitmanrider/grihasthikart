<?php

namespace Database\Seeders;

use App\Models\CashbackRule;
use Illuminate\Database\Seeder;

class CashbackRuleSeeder extends Seeder
{
    public function run(): void
    {
        CashbackRule::query()->updateOrCreate(
            ['name' => 'Default Cashback Rule'],
            [
                'cashback_percent' => 5,
                'monthly_order_threshold' => 5000,
                'eligible_category_threshold_percent' => 50,
                'redemption_multiple' => 500,
                'processing_delay_days' => 2,
                'status' => true,
                'is_default' => true,
            ]
        );
    }
}
