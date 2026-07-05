<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = InventoryMovement::class;

    public function definition(): array
    {
        $inventory = Inventory::factory()->create();

        return [
            'inventory_id' => $inventory->id,
            'product_variant_id' => $inventory->product_variant_id,
            'stock_location_id' => $inventory->stock_location_id,
            'movement_type' => fake()->randomElement(InventoryMovement::TYPES),
            'quantity' => fake()->randomFloat(3, 1, 25),
            'balance_after' => $inventory->quantity_on_hand,
            'reference_type' => null,
            'reference_id' => null,
            'note' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
