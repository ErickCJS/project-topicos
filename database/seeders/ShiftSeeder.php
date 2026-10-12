<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            ['name' => 'Mañana', 'start_time' => '05:00:00', 'end_time' => '13:00:00'],
            ['name' => 'Tarde', 'start_time' => '13:00:00', 'end_time' => '20:00:00'],
            ['name' => 'Noche', 'start_time' => '20:00:00', 'end_time' => '02:00:00'],
        ] as $shift) {
            DB::table('shifts')->updateOrInsert(
                ['name' => $shift['name']],
                [
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
