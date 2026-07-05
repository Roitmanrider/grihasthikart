<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            ['name' => 'Weight', 'type' => 'weight', 'is_filterable' => true, 'is_variant_defining' => true],
            ['name' => 'Volume', 'type' => 'volume', 'is_filterable' => true, 'is_variant_defining' => true],
            ['name' => 'Pack Size', 'type' => 'pack', 'is_filterable' => true, 'is_variant_defining' => true],
            ['name' => 'Flavor', 'type' => 'select', 'is_filterable' => true, 'is_variant_defining' => true],
            ['name' => 'Color', 'type' => 'color', 'is_filterable' => true, 'is_variant_defining' => true],
            ['name' => 'Size', 'type' => 'size', 'is_filterable' => true, 'is_variant_defining' => true],
            ['name' => 'Material', 'type' => 'select', 'is_filterable' => true, 'is_variant_defining' => false],
        ];

        foreach ($attributes as $index => $attribute) {
            Attribute::query()->updateOrCreate(
                ['slug' => str($attribute['name'])->slug()->toString()],
                [
                    'name' => $attribute['name'],
                    'type' => $attribute['type'],
                    'display_order' => $index + 1,
                    'is_filterable' => $attribute['is_filterable'],
                    'is_variant_defining' => $attribute['is_variant_defining'],
                    'status' => true,
                ]
            );
        }
    }
}
