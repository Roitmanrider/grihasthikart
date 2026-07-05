<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BusinessSetting> */
class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    public function definition(): array
    {
        return [
            'group' => 'checkout',
            'key' => fake()->unique()->word(),
            'value' => fake()->word(),
            'value_type' => 'string',
            'label' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'is_public' => false,
            'is_editable' => true,
            'display_order' => fake()->numberBetween(0, 100),
        ];
    }
}
