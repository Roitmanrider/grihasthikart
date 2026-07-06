<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
class WishlistItemFactory extends Factory
{
    protected $model = WishlistItem::class;

    public function definition(): array
    {
        $variant = ProductVariant::factory()->create();

        return [
            'customer_id' => Customer::factory(),
            'session_id' => null,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ];
    }
}
