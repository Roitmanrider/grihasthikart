<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'path' => 'products/'.fake()->uuid().'.jpg',
            'alt_text' => fake()->sentence(3),
            'title' => fake()->words(3, true),
            'display_order' => fake()->numberBetween(0, 100),
            'is_primary' => false,
            'status' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => [
            'is_primary' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
