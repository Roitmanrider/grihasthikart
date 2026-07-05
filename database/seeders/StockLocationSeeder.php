<?php

namespace Database\Seeders;

use App\Models\StockLocation;
use Illuminate\Database\Seeder;

class StockLocationSeeder extends Seeder
{
    public function run(): void
    {
        StockLocation::query()->update(['is_default' => false]);

        StockLocation::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Store',
                'type' => 'store',
                'address' => 'GrihasthiKart Main Road',
                'city' => 'Patna',
                'state' => 'Bihar',
                'pincode' => '800001',
                'is_default' => true,
                'status' => true,
                'display_order' => 0,
            ]
        );
    }
}
