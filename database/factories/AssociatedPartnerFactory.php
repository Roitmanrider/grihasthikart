<?php

namespace Database\Factories;

use App\Models\AssociatedPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssociatedPartner>
 */
class AssociatedPartnerFactory extends Factory
{
    protected $model = AssociatedPartner::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'image_path' => 'uploads/site/partners/logo.webp',
            'external_url' => 'https://example.com',
            'promo_text' => 'UPTO 10% OFF',
            'enabled' => true,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
