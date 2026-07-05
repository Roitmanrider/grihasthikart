<?php

namespace Database\Factories;

use App\Models\DeliverySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DeliverySlot> */
class DeliverySlotFactory extends Factory
{
    protected $model = DeliverySlot::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['7-9 AM', '9-11 AM', '4-6 PM', '6-8 PM']).fake()->numberBetween(1, 999),
            'start_time' => fake()->time('H:i'),
            'end_time' => fake()->time('H:i'),
            'display_label' => null,
            'status' => true,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => false]);
    }
}
