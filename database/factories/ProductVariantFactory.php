<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $variantName = fake()->randomElement(['250g', '500g', '1kg', '5kg', '500ml', '1L', 'Pack of 1']);
        $mrp = fake()->randomFloat(2, 50, 999);
        $sellingPrice = fake()->randomFloat(2, 40, $mrp);

        return [
            'product_id' => Product::factory(),
            'sku' => Str::upper(fake()->unique()->bothify('GK-????-####')),
            'barcode' => fake()->boolean(70) ? fake()->unique()->numerify('890##########') : null,
            'variant_name' => $variantName,
            'attribute_signature' => 'default',
            'weight' => fake()->optional()->randomFloat(3, 0.1, 5),
            'unit' => fake()->randomElement(['g', 'kg', 'ml', 'l', 'pack']),
            'mrp' => $mrp,
            'selling_price' => $sellingPrice,
            'purchase_price' => fake()->optional()->randomFloat(2, 20, $sellingPrice),
            'is_default' => false,
            'status' => true,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
