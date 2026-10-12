<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $employeeIds = DB::table('employees')
            ->where('email', 'like', '%@employees.test')
            ->pluck('id');

        if ($employeeIds->count() !== 10) {
            throw new RuntimeException('ContractSeeder requiere los 10 empleados de prueba.');
        }

        DB::table('contracts')->whereIn('employee_id', $employeeIds)->delete();

        $faker = Factory::create('es_PE');
        $now = now();
        $contracts = [];

        foreach ($employeeIds as $employeeId) {
            $type = $faker->randomElement(['Permanente', 'Nombrado', 'Temporal']);
            $startDate = now()->subDays($faker->numberBetween(0, 15));

            $contracts[] = [
                'employee_id' => $employeeId,
                'type' => $type,
                'start_date' => $startDate->toDateString(),
                'end_date' => $type === 'Temporal'
                    ? $startDate->copy()->addMonthsNoOverflow($faker->numberBetween(2, 6))->toDateString()
                    : null,
                'salary' => $faker->randomFloat(2, 1025, 2500),
                'trial_period_months' => $type === 'Temporal' ? $faker->numberBetween(1, 3) : null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('contracts')->insert($contracts);
    }
}
