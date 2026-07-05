<?php

namespace Database\Factories;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CouponUsage> */
class CouponUsageFactory extends Factory
{
    protected $model = CouponUsage::class;

    public function definition(): array
    {
        return [
            'coupon_id' => Coupon::factory(),
            'order_id' => Order::factory(),
            'customer_id' => null,
            'session_id' => fake()->uuid(),
            'code_snapshot' => strtoupper(fake()->bothify('GK####')),
            'discount_type_snapshot' => 'fixed',
            'discount_value_snapshot' => 50,
            'discount_amount' => 50,
            'cart_subtotal_snapshot' => 500,
            'used_at' => now(),
            'metadata' => null,
        ];
    }
}
