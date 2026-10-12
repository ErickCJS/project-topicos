<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Factory::create('es_PE');
        $employeeTypes = DB::table('employee_types')->whereIn('name', ['Conductor', 'Ayudante'])->pluck('id', 'name');

        if (! $employeeTypes->has('Conductor') || ! $employeeTypes->has('Ayudante')) {
            throw new RuntimeException('Primero deben existir los tipos Conductor y Ayudante.');
        }

        DB::table('employees')->where('email', 'like', '%@employees.test')->delete();

        $now = now();
        $employees = [];

        for ($i = 0; $i < 10; $i++) {
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $employeeTypeId = $faker->randomElement([
                $employeeTypes->get('Conductor'),
                $employeeTypes->get('Ayudante'),
            ]);
            $isDriver = $employeeTypeId === $employeeTypes->get('Conductor');

            $employees[] = [
                'employee_type_id' => $employeeTypeId,
                'dni' => $faker->unique()->numerify('########'),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birthdate' => $faker->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
                'license' => $isDriver ? strtoupper($faker->bothify('Q########')) : null,
                'phone' => $faker->numerify('9########'),
                'address' => $faker->streetAddress(),
                'email' => strtolower($faker->unique()->userName()).'@employees.test',
                'password' => Hash::make('password'),
                'status' => 'Activo',
                'profile_photo_path' => 'https://ui-avatars.com/api/?name='.urlencode($firstName.'+'.$lastName).'&color=7F9CF5&background=EBF4FF',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('employees')->insert($employees);
    }
}
