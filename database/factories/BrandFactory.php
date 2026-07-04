<?php

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Amul',
            'Aashirvaad',
            'Tata Sampann',
            'Fortune',
            'Patanjali',
            'Britannia',
            'Parle',
            'Haldiram',
            'Mother Dairy',
            'Dabur',
            'Nandini',
            'India Gate',
        ]).fake()->unique()->numberBetween(1, 999);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'logo' => null,
            'banner' => null,
            'website_url' => fake()->optional()->url(),
            'meta_title' => $name,
            'meta_description' => fake()->sentence(),
            'meta_keywords' => Str::lower(str_replace(' ', ', ', $name)),
            'is_featured' => fake()->boolean(25),
            'status' => true,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
