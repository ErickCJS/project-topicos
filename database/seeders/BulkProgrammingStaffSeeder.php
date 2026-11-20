<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BulkProgrammingStaffSeeder extends Seeder
{
    /** Personal adicional, determinista y reutilizable para programación masiva. */
    public function run(): void
    {
        $types = DB::table('employee_types')->whereIn('name', ['Conductor', 'Ayudante'])->pluck('id', 'name');
        $now = now();
        $staff = [
            ['Conductor', 'Carlos', 'Mendoza', '80010001'], ['Conductor', 'Diego', 'Salazar', '80010002'],
            ['Conductor', 'Ricardo', 'Paredes', '80010003'], ['Conductor', 'Sergio', 'Quispe', '80010004'],
            ['Conductor', 'Marco', 'Huamán', '80010005'], ['Conductor', 'Óscar', 'Ramos', '80010006'],
            ['Ayudante', 'Andrea', 'Vega', '80010101'], ['Ayudante', 'Bruno', 'Campos', '80010102'],
            ['Ayudante', 'Carmen', 'León', '80010103'], ['Ayudante', 'Daniel', 'Soto', '80010104'],
            ['Ayudante', 'Fiorella', 'Núñez', '80010105'], ['Ayudante', 'Gabriel', 'Mora', '80010106'],
            ['Ayudante', 'Helena', 'Pinto', '80010107'], ['Ayudante', 'Iván', 'Ríos', '80010108'],
            ['Ayudante', 'Julia', 'Cruz', '80010109'], ['Ayudante', 'Kevin', 'Luna', '80010110'],
            ['Ayudante', 'Laura', 'Mendoza', '80010111'], ['Ayudante', 'Mateo', 'Silva', '80010112'],
        ];

        DB::transaction(function () use ($staff, $types, $now) {
            foreach ($staff as [$role, $firstName, $lastName, $dni]) {
                $email = 'masiva.'.$dni.'@rsu.test';
                DB::table('employees')->updateOrInsert(['dni' => $dni], [
                    'employee_type_id' => $types[$role], 'first_name' => $firstName, 'last_name' => $lastName,
                    'birthdate' => '1990-01-01', 'license' => $role === 'Conductor' ? 'Q'.$dni : null,
                    'phone' => '9'.substr($dni, 0, 8), 'address' => 'Personal operativo RSU', 'email' => $email,
                    'password' => Hash::make('password'), 'status' => 'Activo', 'profile_photo_path' => null,
                    'updated_at' => $now, 'created_at' => $now,
                ]);
                $employeeId = DB::table('employees')->where('dni', $dni)->value('id');
                $hasCurrentContract = DB::table('contracts')->where('employee_id', $employeeId)->where('is_active', true)
                    ->whereNull('cancelled_at')->whereDate('start_date', '<=', today())->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))->exists();
                if (! $hasCurrentContract) {
                    DB::table('contracts')->insert(['employee_id' => $employeeId, 'type' => 'Permanente', 'start_date' => today()->subYear()->toDateString(), 'end_date' => null, 'salary' => 1800, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        });
    }
}
