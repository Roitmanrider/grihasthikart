<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            ['GROCERY50', 'Rs. 50 off groceries', 'fixed', 50, null, 499],
            ['FRESH10', '10% off fresh basket', 'percentage', 10, 100, 799],
            ['ATTA25', 'Atta and staples offer', 'fixed', 25, null, 299],
            ['MONTHEND15', 'Month end grocery savings', 'percentage', 15, 150, 999],
        ];

        foreach ($coupons as [$code, $name, $type, $value, $max, $minimum]) {
            Coupon::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'description' => $name.' for GrihasthiKart grocery customers.',
                    'discount_type' => $type,
                    'discount_value' => $value,
                    'max_discount_amount' => $max,
                    'minimum_order_amount' => $minimum,
                    'usage_limit_total' => 1000,
                    'usage_limit_per_customer' => 1,
                    'usage_limit_per_session' => 1,
                    'status' => true,
                    'is_cashback_coupon' => false,
                    'source' => 'promotion',
                    'starts_at' => now()->subDay(),
                    'expires_at' => now()->addMonths(3),
                ]
            );
        }
    }
}
