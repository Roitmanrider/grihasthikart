<?php

namespace Database\Factories;

use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockLocation>
 */
class StockLocationFactory extends Factory
{
    protected $model = StockLocation::class;

    public function definition(): array
    {
        $name = fake()->randomElement(['Main Store', 'Warehouse North', 'Warehouse South', 'City Store']).fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'code' => Str::upper(Str::slug($name, '')),
            'type' => fake()->randomElement(['store', 'warehouse']),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'pincode' => fake()->numerify('######'),
            'is_default' => false,
            'status' => true,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'name' => 'Main Store',
            'code' => 'MAIN',
            'type' => 'store',
            'is_default' => true,
            'status' => true,
            'display_order' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
