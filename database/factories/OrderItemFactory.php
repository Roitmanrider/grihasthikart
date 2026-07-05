<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        return [
            'order_id' => Order::factory(),
            'product_variant_id' => $variant->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'variant_name_snapshot' => $variant->variant_name,
            'sku_snapshot' => $variant->sku,
            'barcode_snapshot' => $variant->barcode,
            'hsn_code_snapshot' => $product->hsn_code,
            'gst_rate_snapshot' => $product->gst_rate,
            'attributes_snapshot' => [],
            'quantity' => 1,
            'mrp' => $variant->mrp,
            'unit_price' => $variant->selling_price,
            'line_subtotal' => $variant->selling_price,
            'line_mrp_total' => $variant->mrp,
            'line_savings' => max(0, (float) $variant->mrp - (float) $variant->selling_price),
            'tax_amount' => 0,
            'line_total' => $variant->selling_price,
        ];
    }
}
