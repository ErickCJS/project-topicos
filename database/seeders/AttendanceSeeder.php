<?php

namespace Database\Seeders;

use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $employeeIds = DB::table('employees')
            ->where('email', 'like', '%@employees.test')
            ->pluck('id');
        $shifts = DB::table('shifts')->get()->keyBy('id');

        if ($employeeIds->isEmpty() || $shifts->count() < 3) {
            throw new RuntimeException('AttendanceSeeder requiere empleados y los 3 turnos registrados.');
        }

        DB::table('attendances')->whereIn('employee_id', $employeeIds)->delete();

        $faker = Factory::create('es_PE');
        $shiftIds = $shifts->keys()->all();
        $now = now();
        $attendances = [];

        for ($i = 0; $i < 20; $i++) {
            $shiftId = $faker->randomElement($shiftIds);
            $shift = $shifts->get($shiftId);
            $startMinute = $this->toMinutes($shift->start_time);
            $endMinute = $this->toMinutes($shift->end_time);

            if ($endMinute <= $startMinute) {
                $endMinute += 24 * 60;
            }

            $minute = $faker->numberBetween($startMinute, $endMinute - 1) % (24 * 60);
            $time = sprintf('%02d:%02d:00', intdiv($minute, 60), $minute % 60);

            $attendances[] = [
                'employee_id' => $faker->randomElement($employeeIds->all()),
                'shift_id' => $shiftId,
                'date' => now()->subDays($faker->numberBetween(0, 14))->toDateString(),
                'time' => $time,
                'type' => $faker->randomElement(['Entrada', 'Salida']),
                'status' => 'Presente',
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('attendances')->insert($attendances);
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}
