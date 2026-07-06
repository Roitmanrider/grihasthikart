<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContactMessage> */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => fake()->optional()->numerify('9#########'),
            'email' => fake()->optional()->safeEmail(),
            'subject' => fake()->optional()->sentence(4),
            'message' => fake()->paragraph(),
            'status' => 'new',
        ];
    }
}
