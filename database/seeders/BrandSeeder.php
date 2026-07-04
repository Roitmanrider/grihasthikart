<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Amul', 'website_url' => 'https://amul.com', 'is_featured' => true],
            ['name' => 'Aashirvaad', 'website_url' => 'https://www.aashirvaad.com', 'is_featured' => true],
            ['name' => 'Tata Sampann', 'website_url' => 'https://www.tatasampann.com', 'is_featured' => true],
            ['name' => 'Fortune Foods', 'website_url' => 'https://www.fortunefoods.com', 'is_featured' => true],
            ['name' => 'India Gate', 'website_url' => 'https://www.krblrice.com', 'is_featured' => false],
            ['name' => 'Patanjali', 'website_url' => 'https://www.patanjaliayurved.net', 'is_featured' => false],
            ['name' => 'Britannia', 'website_url' => 'https://www.britannia.co.in', 'is_featured' => true],
            ['name' => 'Parle', 'website_url' => 'https://www.parleproducts.com', 'is_featured' => false],
            ['name' => 'Haldiram', 'website_url' => 'https://www.haldirams.com', 'is_featured' => true],
            ['name' => 'Mother Dairy', 'website_url' => 'https://www.motherdairy.com', 'is_featured' => true],
            ['name' => 'Dabur', 'website_url' => 'https://www.dabur.com', 'is_featured' => false],
            ['name' => 'Nandini', 'website_url' => 'https://www.kmfnandini.coop', 'is_featured' => false],
        ];

        foreach ($brands as $index => $brand) {
            Brand::query()->updateOrCreate(
                ['slug' => str($brand['name'])->slug()->toString()],
                [
                    'name' => $brand['name'],
                    'description' => $brand['name'].' grocery and FMCG products for Indian households.',
                    'website_url' => $brand['website_url'],
                    'meta_title' => $brand['name'].' Products Online',
                    'meta_description' => 'Shop '.$brand['name'].' grocery essentials online at GrihasthiKart.',
                    'meta_keywords' => str($brand['name'])->lower()->replace(' ', ', ')->toString(),
                    'is_featured' => $brand['is_featured'],
                    'status' => true,
                    'display_order' => $index + 1,
                ]
            );
        }
    }
}
