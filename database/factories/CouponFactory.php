<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Coupon> */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('GK####')),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'discount_type' => 'fixed',
            'discount_value' => 50,
            'max_discount_amount' => null,
            'minimum_order_amount' => 0,
            'usage_limit_total' => null,
            'usage_limit_per_customer' => null,
            'usage_limit_per_session' => null,
            'customer_id' => null,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'status' => true,
            'is_cashback_coupon' => false,
            'source' => 'admin',
            'metadata' => null,
        ];
    }

    public function percentage(): self
    {
        return $this->state(fn () => [
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'max_discount_amount' => 100,
        ]);
    }
}
