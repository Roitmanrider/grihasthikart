<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductVariantSeeder extends Seeder
{
    public function run(): void
    {
        $variants = [
            'Wheat Atta' => [['1kg', 'Weight', '1kg', 75, 68, 1.000, 'kg'], ['5kg', 'Weight', '5kg', 365, 339, 5.000, 'kg']],
            'Basmati Rice' => [['1kg', 'Weight', '1kg', 145, 129, 1.000, 'kg'], ['5kg', 'Weight', '5kg', 699, 649, 5.000, 'kg']],
            'Sunflower Oil' => [['1L', 'Volume', '1L', 180, 165, 1.000, 'l'], ['5L', 'Volume', '5L', 850, 799, 5.000, 'l']],
            'Toned Milk' => [['500ml', 'Volume', '500ml', 34, 32, 0.500, 'l'], ['1L', 'Volume', '1L', 66, 64, 1.000, 'l']],
            'Turmeric Powder' => [['100g', 'Weight', '100g', 42, 38, 0.100, 'kg'], ['200g', 'Weight', '200g', 80, 72, 0.200, 'kg']],
            'Red Chilli Powder' => [['100g', 'Weight', '100g', 55, 49, 0.100, 'kg'], ['200g', 'Weight', '200g', 105, 95, 0.200, 'kg']],
            'Salt' => [['1kg', 'Weight', '1kg', 28, 25, 1.000, 'kg']],
            'Sugar' => [['1kg', 'Weight', '1kg', 55, 49, 1.000, 'kg'], ['5kg', 'Weight', '5kg', 270, 249, 5.000, 'kg']],
            'Tea' => [['250g', 'Weight', '250g', 160, 145, 0.250, 'kg'], ['500g', 'Weight', '500g', 310, 285, 0.500, 'kg']],
            'Biscuits' => [['Pack of 1', 'Pack Size', 'Pack of 1', 30, 27, 1.000, 'pack'], ['Pack of 4', 'Pack Size', 'Pack of 4', 115, 105, 4.000, 'pack']],
        ];

        foreach ($variants as $productName => $productVariants) {
            $product = Product::query()->where('name', $productName)->first();

            if (! $product) {
                continue;
            }

            foreach ($productVariants as $index => [$variantName, $attributeName, $attributeValue, $mrp, $sellingPrice, $weight, $unit]) {
                $attribute = Attribute::query()->where('name', $attributeName)->first();

                $value = $attribute
                    ? AttributeValue::query()->firstOrCreate(
                        [
                            'attribute_id' => $attribute->id,
                            'slug' => Str::slug($attributeValue),
                        ],
                        [
                            'value' => $attributeValue,
                            'display_order' => 0,
                            'status' => true,
                        ]
                    )
                    : null;

                $signature = $value ? (string) $value->id : 'default';
                $sku = 'GK-'.Str::upper(Str::slug($productName, '')).'-'.Str::upper(Str::slug($variantName, ''));

                $variant = ProductVariant::query()->updateOrCreate(
                    ['sku' => $sku],
                    [
                        'product_id' => $product->id,
                        'barcode' => null,
                        'variant_name' => $variantName,
                        'attribute_signature' => $signature,
                        'weight' => $weight,
                        'unit' => $unit,
                        'mrp' => $mrp,
                        'selling_price' => $sellingPrice,
                        'purchase_price' => null,
                        'is_default' => $index === 0,
                        'status' => true,
                        'display_order' => $index,
                    ]
                );

                $variant->attributeValues()->sync($value ? [$value->id => ['attribute_id' => $value->attribute_id]] : []);

                if ($variant->is_default) {
                    ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->whereKeyNot($variant->id)
                        ->update(['is_default' => false]);

                    $product->forceFill(['default_variant_id' => $variant->id])->save();
                }
            }
        }
    }
}
