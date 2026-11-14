<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(DevelopmentTestUserSeeder::class);
        $this->call([
            EmployeeTypeSeeder::class,
            ShiftSeeder::class,
            EmployeeSeeder::class,
            ContractSeeder::class,
            BulkProgrammingStaffSeeder::class,
            OperationalStaffSeeder::class,
            VacationSeeder::class,
            HolidaySeeder::class,
            AttendanceSeeder::class,
            CatalogSeeder::class,
            ReasonSeeder::class,
        ]);
    }
}
