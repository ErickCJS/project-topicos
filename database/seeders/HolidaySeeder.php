<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('holidays')->upsert([
            [
                'name' => 'Día de la Ciudad',
                'date' => '2026-01-12',
                'description' => 'Feriado local ficticio por aniversario de la ciudad.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Jornada Cívica Regional',
                'date' => '2026-03-20',
                'description' => 'Feriado regional ficticio.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Día del Servicio Público',
                'date' => '2026-05-08',
                'description' => 'Feriado institucional ficticio.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Festival Cultural Local',
                'date' => '2026-08-14',
                'description' => 'Feriado local ficticio por actividad cultural.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Día de Integración Comunitaria',
                'date' => '2026-11-06',
                'description' => 'Feriado comunitario ficticio.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['date'], ['name', 'description', 'updated_at']);
    }
}
