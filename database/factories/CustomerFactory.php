<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => fake()->unique()->numerify('9#########'),
            'email' => fake()->safeEmail(),
            'status' => true,
            'is_premium' => false,
            'cashback_enabled' => false,
            'monthly_cashback_threshold' => null,
            'category_cashback_threshold_percent' => null,
            'custom_delivery_rules_enabled' => false,
            'minimum_order_amount_override' => null,
            'delivery_charge_override' => null,
            'free_delivery_threshold_override' => null,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }
}
