<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ContractEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            $typeIds = [];
            foreach (['Conductor', 'Ayudante'] as $name) {
                $typeIds[$name] = DB::table('employee_types')->where('name', $name)->value('id');
                if (!$typeIds[$name]) {
                    $typeIds[$name] = DB::table('employee_types')->insertGetId([
                        'name' => $name, 'description' => 'Personal operativo municipal.',
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }

            $names = [
                ['Ana', 'Perez'], ['Luis', 'Gomez'], ['Maria', 'Torres'], ['Carlos', 'Ruiz'],
                ['Rosa', 'Diaz'], ['Jose', 'Vargas'], ['Elena', 'Castro'], ['Pedro', 'Rojas'],
                ['Lucia', 'Flores'], ['Miguel', 'Sanchez'],
            ];
            $password = Hash::make('password');
            foreach ($names as $index => [$firstName, $lastName]) {
                $number = $index + 1;
                $dni = (string) (99001000 + $number);
                $email = sprintf('contratos.%02d@example.test', $number);

                // Never overwrite a worker, reset a password, or remove their contract history.
                if (DB::table('employees')->where('dni', $dni)->orWhere('email', $email)->exists()) {
                    continue;
                }

                $type = $index % 2 === 0 ? 'Conductor' : 'Ayudante';
                DB::table('employees')->insert([
                    'employee_type_id' => $typeIds[$type], 'dni' => $dni,
                    'first_name' => $firstName, 'last_name' => $lastName,
                    'birthdate' => '1990-01-01', 'license' => $type === 'Conductor' ? 'Q'.$dni : null,
                    'phone' => (string) (990000000 + $number), 'address' => 'Dirección de prueba',
                    'email' => $email, 'password' => $password, 'status' => 'Activo',
                    'profile_photo_path' => null, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        });

        $this->command?->info('Personal de prueba disponible para contratos (DNI 99001001 a 99001010). No se modificaron trabajadores ni contratos existentes.');
    }
}
