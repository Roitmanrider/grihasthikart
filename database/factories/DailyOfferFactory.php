<?php

namespace Database\Factories;

use App\Models\DailyOffer;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyOffer>
 */
class DailyOfferFactory extends Factory
{
    protected $model = DailyOffer::class;

    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'title' => null,
            'offer_price' => fake()->randomFloat(2, 20, 200),
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
            'display_order' => fake()->numberBetween(0, 100),
            'max_quantity_per_order' => null,
            'badge_text' => fake()->optional()->randomElement(['Deal', 'Today Only', 'Fresh Deal']),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);
    }
}
