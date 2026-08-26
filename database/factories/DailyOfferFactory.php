<?php

namespace Database\Factories;

use App\Models\DailyOffer;
use App\Models\ProductVariant;
use App\Models\StockLocation;
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
            'stock_location_id' => fn () => StockLocation::query()->where('is_default', true)->value('id') ?? StockLocation::factory()->default(),
            'title' => null,
            'offer_price' => fake()->randomFloat(2, 20, 200),
            'allocated_quantity' => 10,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
            'display_order' => fake()->numberBetween(1, 100),
            'max_quantity_per_order' => 3,
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
