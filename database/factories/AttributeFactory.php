<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Weight',
            'Volume',
            'Pack Size',
            'Flavor',
            'Color',
            'Size',
            'Material',
        ]).fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => fake()->randomElement(Attribute::TYPES),
            'display_order' => fake()->numberBetween(0, 100),
            'is_filterable' => fake()->boolean(70),
            'is_variant_defining' => fake()->boolean(50),
            'status' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
