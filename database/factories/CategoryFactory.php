<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Fresh Vegetables',
            'Fresh Fruits',
            'Dairy Products',
            'Bakery',
            'Rice and Grains',
            'Pulses and Lentils',
            'Cooking Oils',
            'Spices and Masala',
            'Snacks',
            'Beverages',
            'Personal Care',
            'Home Cleaning',
        ]).fake()->unique()->numberBetween(1, 999);

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'image' => null,
            'banner' => null,
            'icon' => null,
            'meta_title' => $name,
            'meta_description' => fake()->sentence(),
            'meta_keywords' => Str::lower(str_replace(' ', ', ', $name)),
            'display_order' => fake()->numberBetween(0, 100),
            'is_featured' => fake()->boolean(20),
            'show_in_menu' => true,
            'show_on_homepage' => fake()->boolean(30),
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
