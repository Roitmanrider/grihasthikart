<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customer = Customer::query()->updateOrCreate(
            ['mobile' => '9876543210'],
            [
                'name' => 'Rohit Kumar',
                'email' => 'rohit@example.com',
                'status' => true,
                'is_premium' => true,
                'cashback_enabled' => false,
            ]
        );

        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'label' => 'Home'],
            [
                'recipient_name' => $customer->name,
                'mobile' => $customer->mobile,
                'address_line1' => 'House 12, Main Road',
                'city' => 'Patna',
                'state' => 'Bihar',
                'pincode' => '800001',
                'is_default' => true,
                'is_approved' => true,
                'status' => true,
            ]
        );
    }
}
