<?php

namespace Database\Factories;

use App\Models\HomepageBanner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HomepageBanner>
 */
class HomepageBannerFactory extends Factory
{
    protected $model = HomepageBanner::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'subtitle' => fake()->sentence(4),
            'cta_text' => 'Shop Now',
            'cta_url' => '/products',
            'open_in_new_tab' => false,
            'alt_text' => fake()->words(3, true),
            'desktop_image_path' => 'uploads/site/banners/banner.webp',
            'mobile_image_path' => null,
            'enabled' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }
}
