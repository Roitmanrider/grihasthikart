<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'contact_person' => fake()->name(),
            'mobile' => fake()->numerify('9#########'),
            'email' => fake()->safeEmail(),
            'gstin' => strtoupper(fake()->bothify('##AAAAA####A#Z#')),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'pincode' => fake()->postcode(),
            'opening_balance' => fake()->randomFloat(2, 0, 10000),
            'status' => Supplier::STATUS_ACTIVE,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Supplier::STATUS_INACTIVE,
        ]);
    }
}
