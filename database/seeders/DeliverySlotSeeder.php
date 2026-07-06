<?php

namespace Database\Seeders;

use App\Models\DeliverySlot;
use Illuminate\Database\Seeder;

class DeliverySlotSeeder extends Seeder
{
    public function run(): void
    {
        $slots = [
            ['7-9 AM', '07:00', '09:00', '7 AM - 9 AM', 1],
            ['9-11 AM', '09:00', '11:00', '9 AM - 11 AM', 2],
            ['4-6 PM', '16:00', '18:00', '4 PM - 6 PM', 3],
            ['6-8 PM', '18:00', '20:00', '6 PM - 8 PM', 4],
        ];

        foreach ($slots as [$name, $start, $end, $label, $order]) {
            DeliverySlot::query()->updateOrCreate(
                ['name' => $name],
                [
                    'start_time' => $start,
                    'end_time' => $end,
                    'display_label' => $label,
                    'status' => true,
                    'display_order' => $order,
                ]
            );
        }
    }
}
