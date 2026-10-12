<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            EmployeeTypeSeeder::class,
            ShiftSeeder::class,
            EmployeeSeeder::class,
            ContractSeeder::class,
            VacationSeeder::class,
            AttendanceSeeder::class,
            CatalogSeeder::class,
        ]);
    }
}
