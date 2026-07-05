<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        $variant = ProductVariant::factory()->create();

        return [
            'cart_id' => Cart::factory(),
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => $variant->selling_price,
            'mrp' => $variant->mrp,
            'product_name_snapshot' => $variant->product->name,
            'variant_name_snapshot' => $variant->variant_name,
            'sku_snapshot' => $variant->sku,
            'hsn_code_snapshot' => $variant->product->hsn_code,
            'gst_rate_snapshot' => $variant->product->gst_rate,
            'attributes_snapshot' => [],
        ];
    }
}
