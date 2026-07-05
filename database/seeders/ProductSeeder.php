<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['Wheat Atta', 'Aashirvaad', ['Staples', 'Flours & Sooji'], '1101', 5, '12 months'],
            ['Basmati Rice', 'India Gate', ['Staples', 'Rice & Rice Products'], '1006', 5, '24 months'],
            ['Sunflower Oil', 'Fortune', ['Edible Oils & Ghee'], '1512', 5, '9 months'],
            ['Toned Milk', 'Mother Dairy', ['Dairy & Bakery'], '0401', 5, '2 days'],
            ['Turmeric Powder', 'Tata Sampann', ['Masala & Spices'], '0910', 5, '12 months'],
            ['Red Chilli Powder', 'Tata Sampann', ['Masala & Spices'], '0904', 5, '12 months'],
            ['Salt', 'Tata Salt', ['Staples'], '2501', 0, '24 months'],
            ['Sugar', 'Madhur', ['Staples'], '1701', 5, '24 months'],
            ['Tea', 'Tata Tea', ['Beverages'], '0902', 5, '12 months'],
            ['Biscuits', 'Britannia', ['Snacks & Packaged Food'], '1905', 18, '6 months'],
        ];

        foreach ($products as [$name, $brandName, $categoryNames, $hsnCode, $gstRate, $shelfLife]) {
            $brand = Brand::query()->firstOrCreate(
                ['slug' => Str::slug($brandName)],
                ['name' => $brandName, 'status' => true, 'display_order' => 0]
            );

            $product = Product::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'brand_id' => $brand->id,
                    'name' => $name,
                    'short_description' => 'Quality '.$name.' for everyday Indian grocery needs.',
                    'description' => $name.' prepared for catalog listing. Sellable variants will be added separately.',
                    'hsn_code' => $hsnCode,
                    'gst_rate' => $gstRate,
                    'manufacturer' => $brandName,
                    'country_of_origin' => 'India',
                    'shelf_life' => $shelfLife,
                    'minimum_order_quantity' => 1,
                    'returnable' => true,
                    'cod_available' => true,
                    'status' => true,
                    'display_order' => 0,
                    'meta_title' => $name.' Online',
                    'meta_description' => 'Buy '.$name.' online from GrihasthiKart.',
                    'meta_keywords' => Str::lower(str_replace(' ', ', ', $name)),
                ]
            );

            $payload = [];

            foreach ($categoryNames as $index => $categoryName) {
                $category = Category::query()->firstOrCreate(
                    ['slug' => Str::slug($categoryName)],
                    ['name' => $categoryName, 'status' => true, 'display_order' => 0]
                );

                $payload[$category->id] = [
                    'is_primary' => $index === 0,
                    'display_order' => $index,
                ];
            }

            $product->categories()->sync($payload);
        }
    }
}
