<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Fruits & Vegetables' => ['Fresh Vegetables', 'Fresh Fruits', 'Herbs & Seasoning'],
            'Dairy & Bakery' => ['Milk', 'Curd & Yogurt', 'Bread & Buns', 'Paneer & Cheese'],
            'Staples' => ['Atta & Flour', 'Rice & Grains', 'Pulses & Lentils', 'Edible Oils', 'Spices & Masala'],
            'Snacks & Beverages' => ['Biscuits', 'Namkeen', 'Tea & Coffee', 'Juices & Soft Drinks'],
            'Personal Care' => ['Hair Care', 'Skin Care', 'Oral Care', 'Bath & Body'],
            'Home Care' => ['Detergents', 'Dishwash', 'Floor Cleaners', 'Pooja Essentials'],
            'Baby Care' => ['Baby Food', 'Diapers', 'Baby Skin Care'],
            'Pet Care' => ['Dog Food', 'Cat Food', 'Pet Grooming'],
        ];

        $order = 1;

        foreach ($categories as $parentName => $children) {
            $parent = Category::query()->firstOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'name' => $parentName,
                    'description' => $parentName.' for everyday Indian households.',
                    'meta_title' => $parentName,
                    'meta_description' => 'Shop '.$parentName.' at GrihasthiKart.',
                    'meta_keywords' => Str::lower(str_replace([' & ', ' '], [', ', ', '], $parentName)),
                    'display_order' => $order,
                    'is_featured' => $order <= 4,
                    'show_in_menu' => true,
                    'show_on_homepage' => $order <= 6,
                    'status' => true,
                ]
            );

            $childOrder = 1;

            foreach ($children as $childName) {
                Category::query()->firstOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'description' => $childName.' category curated for daily grocery needs.',
                        'meta_title' => $childName,
                        'meta_description' => 'Buy '.$childName.' online from GrihasthiKart.',
                        'meta_keywords' => Str::lower(str_replace([' & ', ' '], [', ', ', '], $childName)),
                        'display_order' => $childOrder,
                        'is_featured' => $childOrder <= 2,
                        'show_in_menu' => true,
                        'show_on_homepage' => false,
                        'status' => true,
                    ]
                );

                $childOrder++;
            }

            $order++;
        }
    }
}
