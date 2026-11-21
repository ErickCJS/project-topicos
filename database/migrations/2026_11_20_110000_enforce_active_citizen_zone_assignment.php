<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SQLITE_INDEX = 'citizen_zone_assignments_one_active_index';
    private const MYSQL_INDEX = 'citizen_zone_assignments_active_citizen_unique';

    public function up(): void
    {
        $this->closeDuplicateActiveAssignments();
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX '.self::SQLITE_INDEX.' ON citizen_zone_assignments (citizen_id) WHERE ended_at IS NULL');
            return;
        }

        if ($driver === 'mysql') {
            $this->validateMysqlVersion();

            Schema::table('citizen_zone_assignments', function ($table): void {
                $table->unsignedBigInteger('active_citizen_id')
                    ->nullable()
                    ->virtualAs('IF(ended_at IS NULL, citizen_id, NULL)');
                $table->unique('active_citizen_id', self::MYSQL_INDEX);
            });
            return;
        }

        throw new RuntimeException('El motor de base de datos no tiene una estrategia de unicidad de asignación activa configurada.');
    }

    private function validateMysqlVersion(): void
    {
        $version = DB::connection()->getServerVersion();

        if (! preg_match('/^(\d+)\.(\d+)/', $version, $matches)
            || ((int) $matches[1] < 5 || ((int) $matches[1] === 5 && (int) $matches[2] < 7))) {
            throw new RuntimeException('MySQL 5.7 o superior es necesario para la columna generada de asignaciones activas.');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS '.self::SQLITE_INDEX);
            return;
        }

        if ($driver === 'mysql' && Schema::hasColumn('citizen_zone_assignments', 'active_citizen_id')) {
            Schema::table('citizen_zone_assignments', function ($table): void {
                $table->dropUnique(self::MYSQL_INDEX);
                $table->dropColumn('active_citizen_id');
            });
        }
    }

    private function closeDuplicateActiveAssignments(): void
    {
        DB::transaction(function (): void {
            $plans = [];

            DB::table('citizen_zone_assignments')
                ->select('citizen_id')
                ->whereNull('ended_at')
                ->groupBy('citizen_id')
                ->havingRaw('COUNT(*) > 1')
                ->orderBy('citizen_id')
                ->get()
                ->each(function (object $duplicate) use (&$plans): void {
                    $assignments = DB::table('citizen_zone_assignments')
                        ->where('citizen_id', $duplicate->citizen_id)
                        ->whereNull('ended_at')
                        ->orderByDesc('assigned_at')
                        ->orderByDesc('id')
                        ->get();
                    $citizenZoneId = DB::table('citizens')->where('id', $duplicate->citizen_id)->value('zone_id');
                    $candidateAssignments = $citizenZoneId === null
                        ? $assignments
                        : $assignments->where('zone_id', $citizenZoneId)->values();

                    if ($candidateAssignments->isEmpty()
                        || ($citizenZoneId === null && $assignments->pluck('zone_id')->unique()->count() > 1)) {
                        throw new RuntimeException('No se puede crear la restricción: existen asignaciones activas ambiguas para el ciudadano '.$duplicate->citizen_id.'.');
                    }

                    $keep = $candidateAssignments->first();
                    $plans[] = [
                        'keep' => $keep->id,
                        'close' => $assignments->where('id', '!=', $keep->id)->pluck('id')->all(),
                    ];
                });

            foreach ($plans as $plan) {
                if ($plan['close'] !== []) {
                    DB::table('citizen_zone_assignments')
                        ->whereIn('id', $plan['close'])
                        ->update(['ended_at' => now()]);
                }
            }
        });
    }
};
