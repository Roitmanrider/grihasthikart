<?php

namespace Database\Factories;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AttributeValue>
 */
class AttributeValueFactory extends Factory
{
    protected $model = AttributeValue::class;

    public function definition(): array
    {
        $value = fake()->randomElement([
            '250g',
            '500g',
            '1kg',
            '250ml',
            '500ml',
            'Pack of 2',
            'Plain',
            'Masala',
            'Red',
            'Green',
            'Small',
            'Medium',
            'Steel',
            'Glass',
        ]).fake()->unique()->numberBetween(1, 999);

        return [
            'attribute_id' => Attribute::factory(),
            'value' => $value,
            'slug' => Str::slug($value),
            'display_order' => fake()->numberBetween(0, 100),
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
