<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            ['name' => 'Conductor', 'description' => 'Personal operativo encargado de conducir vehículos municipales.'],
            ['name' => 'Ayudante', 'description' => 'Personal operativo de apoyo en las labores municipales.'],
        ] as $type) {
            DB::table('employee_types')->updateOrInsert(
                ['name' => $type['name']],
                [
                    'description' => $type['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
