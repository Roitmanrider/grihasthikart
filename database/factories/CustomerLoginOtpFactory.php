<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerLoginOtp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<CustomerLoginOtp> */
class CustomerLoginOtpFactory extends Factory
{
    protected $model = CustomerLoginOtp::class;

    public function definition(): array
    {
        $customer = Customer::factory()->create();

        return [
            'customer_id' => $customer->id,
            'mobile' => $customer->mobile,
            'otp_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'verified_at' => null,
            'attempts' => 0,
        ];
    }
}
