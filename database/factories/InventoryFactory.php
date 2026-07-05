<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\ProductVariant;
use App\Models\StockLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        $quantityOnHand = fake()->randomFloat(3, 10, 250);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'stock_location_id' => StockLocation::factory(),
            'quantity_on_hand' => $quantityOnHand,
            'reserved_quantity' => fake()->randomFloat(3, 0, 5),
            'damaged_quantity' => fake()->randomFloat(3, 0, 2),
            'low_stock_threshold' => fake()->randomFloat(3, 5, 20),
            'reorder_level' => fake()->randomFloat(3, 10, 30),
            'target_stock_level' => fake()->randomFloat(3, 100, 300),
            'status' => true,
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => [
            'quantity_on_hand' => 5,
            'reserved_quantity' => 0,
            'damaged_quantity' => 0,
            'low_stock_threshold' => 10,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => false,
        ]);
    }
}
