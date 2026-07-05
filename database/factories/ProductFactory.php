<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Wheat Atta',
            'Basmati Rice',
            'Sunflower Oil',
            'Toned Milk',
            'Turmeric Powder',
            'Red Chilli Powder',
            'Iodized Salt',
            'Sugar',
            'Assam Tea',
            'Glucose Biscuits',
        ]).fake()->unique()->numberBetween(1, 999);

        return [
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'barcode' => fake()->optional()->numerify('890##########'),
            'hsn_code' => fake()->optional()->numerify('####'),
            'gst_rate' => fake()->randomElement([0, 5, 12, 18]),
            'manufacturer' => fake()->company(),
            'country_of_origin' => 'India',
            'shelf_life' => fake()->randomElement(['3 months', '6 months', '9 months', '12 months']),
            'minimum_order_quantity' => 1,
            'maximum_order_quantity' => fake()->optional()->numberBetween(5, 20),
            'returnable' => true,
            'cod_available' => true,
            'is_featured' => fake()->boolean(20),
            'is_trending' => fake()->boolean(20),
            'is_popular' => fake()->boolean(20),
            'is_new_arrival' => fake()->boolean(20),
            'status' => true,
            'display_order' => fake()->numberBetween(0, 100),
            'meta_title' => $name,
            'meta_description' => fake()->sentence(),
            'meta_keywords' => Str::lower(str_replace(' ', ', ', $name)),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
