<?php

namespace Database\Seeders;

use App\Models\DeliverySlot;
use Illuminate\Database\Seeder;

class DeliverySlotSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['7-9 AM', '07:00', '09:00', 1],
            ['9-11 AM', '09:00', '11:00', 2],
            ['4-6 PM', '16:00', '18:00', 3],
            ['6-8 PM', '18:00', '20:00', 4],
        ];

        foreach ($slots as [$name, $start, $end, $order]) {
            DeliverySlot::query()->updateOrCreate(
                ['name' => $name],
                [
                    'start_time' => $start,
                    'end_time' => $end,
                    'display_label' => $name,
                    'status' => true,
                    'display_order' => $order,
                ]
            );
        }
    }
}
