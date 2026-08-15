<?php

namespace Database\Factories;

use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageSection>
 */
class HomepageSectionFactory extends Factory
{
    protected $model = HomepageSection::class;

    public function definition(): array
    {
        return [
            'section_key' => fake()->unique()->slug(2),
            'section_type' => 'static',
            'title' => fake()->words(3, true),
            'enabled' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'desktop_item_limit' => 8,
            'source_mode' => 'automatic',
            'view_all_enabled' => true,
        ];
    }
}
