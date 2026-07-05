<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'order_number' => 'GK'.now()->format('ymd').fake()->unique()->bothify('????##'),
            'cart_id' => null,
            'session_id' => fake()->uuid(),
            'customer_id' => null,
            'customer_name' => fake()->name(),
            'customer_mobile' => fake()->numerify('9#########'),
            'customer_email' => fake()->safeEmail(),
            'delivery_address_line1' => fake()->streetAddress(),
            'delivery_address_line2' => null,
            'delivery_city' => fake()->city(),
            'delivery_state' => fake()->state(),
            'delivery_pincode' => fake()->numerify('######'),
            'delivery_landmark' => null,
            'delivery_date' => now()->toDateString(),
            'delivery_slot' => '9-11 AM',
            'payment_method' => 'cod',
            'payment_status' => 'pending',
            'order_status' => 'placed',
            'subtotal' => 100,
            'total_mrp' => 120,
            'total_savings' => 20,
            'tax_total' => 4.76,
            'delivery_charge' => 0,
            'discount_total' => 0,
            'grand_total' => 100,
            'notes' => null,
            'admin_notes' => null,
            'placed_at' => now(),
        ];
    }
}
