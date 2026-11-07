<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class OperationalStaffSeeder extends Seeder
{
    public function run(): void
    {
        $typeIds = DB::table('employee_types')
            ->whereIn('name', ['Conductor', 'Ayudante'])
            ->pluck('id', 'name');

        if (! $typeIds->has('Conductor') || ! $typeIds->has('Ayudante')) {
            throw new RuntimeException('Primero deben existir los tipos Conductor y Ayudante.');
        }

        $faker = Factory::create('es_PE');
        $faker->seed(20261107);
        $password = Hash::make('password');
        $staff = [];

        foreach ([['Conductor', 25, 93000000], ['Ayudante', 65, 93010000]] as [$role, $count, $dniPrefix]) {
            for ($number = 1; $number <= $count; $number++) {
                $dni = (string) ($dniPrefix + $number);
                $firstName = $faker->firstName();
                $lastName = $faker->lastName();

                $staff[] = [
                    'role' => $role,
                    'dni' => $dni,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'birthdate' => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                    'license' => $role === 'Conductor' ? 'Q'.$dni : null,
                    'phone' => $faker->numerify('9########'),
                    'address' => $faker->streetAddress(),
                    'email' => 'operativo.'.$dni.'@rsu.test',
                ];
            }
        }

        DB::transaction(function () use ($staff, $typeIds, $password): void {
            foreach ($staff as $person) {
                DB::table('employees')->updateOrInsert(
                    ['dni' => $person['dni']],
                    [
                        'employee_type_id' => $typeIds[$person['role']],
                        'first_name' => $person['first_name'],
                        'last_name' => $person['last_name'],
                        'birthdate' => $person['birthdate'],
                        'license' => $person['license'],
                        'phone' => $person['phone'],
                        'address' => $person['address'],
                        'email' => $person['email'],
                        'password' => $password,
                        'status' => 'Activo',
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $employeeId = DB::table('employees')->where('dni', $person['dni'])->value('id');
                $hasCurrentContract = DB::table('contracts')
                    ->where('employee_id', $employeeId)
                    ->where('is_active', true)
                    ->whereNull('cancelled_at')
                    ->whereDate('start_date', '<=', today())
                    ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                    ->exists();

                if (! $hasCurrentContract) {
                    DB::table('contracts')->insert([
                        'employee_id' => $employeeId,
                        'type' => 'Permanente',
                        'start_date' => today()->subYear()->toDateString(),
                        'end_date' => null,
                        'salary' => 1800,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }
}