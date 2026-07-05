<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Database\Seeder;

class AttributeValueSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'Weight' => ['250g', '500g', '1kg', '5kg'],
            'Volume' => ['250ml', '500ml', '1L', '5L'],
            'Pack Size' => ['Pack of 1', 'Pack of 2', 'Pack of 4', 'Pack of 6'],
            'Flavor' => ['Plain', 'Masala', 'Salted', 'Sweet'],
            'Color' => ['Red', 'Green', 'Yellow', 'Black', 'White'],
            'Size' => ['Small', 'Medium', 'Large'],
            'Material' => ['Plastic', 'Steel', 'Glass', 'Paper'],
        ];

        foreach ($values as $attributeName => $attributeValues) {
            $attribute = Attribute::query()->where('name', $attributeName)->first();

            if (! $attribute) {
                continue;
            }

            foreach ($attributeValues as $index => $value) {
                AttributeValue::query()->updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                        'slug' => str($value)->slug()->toString(),
                    ],
                    [
                        'value' => $value,
                        'display_order' => $index + 1,
                        'status' => true,
                    ]
                );
            }
        }
    }
}
