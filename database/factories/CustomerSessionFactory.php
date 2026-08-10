<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CustomerSession> */
class CustomerSessionFactory extends Factory
{
    protected $model = CustomerSession::class;

    public function definition(): array
    {
        $loggedInAt = now();

        return [
            'customer_id' => Customer::factory(),
            'session_token_hash' => hash('sha256', Str::random(64)),
            'device_label' => 'Test Browser',
            'ip_address' => '127.0.0.1',
            'logged_in_at' => $loggedInAt,
            'last_seen_at' => $loggedInAt,
            'expires_at' => $loggedInAt->copy()->addDays(21),
            'revoked_at' => null,
        ];
    }
}
