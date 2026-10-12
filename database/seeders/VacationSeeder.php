<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VacationSeeder extends Seeder
{
    public function run(): void
    {
        $employeeIds = DB::table('employees')
            ->where('email', 'like', '%@employees.test')
            ->pluck('id');

        DB::table('vacations')->whereIn('employee_id', $employeeIds)->delete();

        $eligibleEmployeeIds = DB::table('contracts')
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('type', ['Permanente', 'Nombrado'])
            ->pluck('employee_id');

        $faker = Factory::create('es_PE');
        $now = now();
        $vacations = [];

        foreach ($eligibleEmployeeIds as $employeeId) {
            $days = $faker->numberBetween(15, 30);
            $vacations[] = [
                'employee_id' => $employeeId,
                'year' => 2026,
                'scheduled_days' => $days,
                'pending_days' => $days,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($vacations !== []) {
            DB::table('vacations')->insert($vacations);
        }
    }
}
