<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLE = 'staff_group_helpers';
    private const UNIQUE_INDEX = 'staff_group_helpers_staff_group_id_employee_id_unique';
    private const EMPLOYEE_INDEX = 'staff_group_helpers_employee_id_index';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            Schema::create(self::TABLE, function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_group_id')
                    ->constrained('staff_groups')
                    ->cascadeOnDelete();
                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();
                $table->timestamps();

                $table->unique(['staff_group_id', 'employee_id'], self::UNIQUE_INDEX);
                $table->index('employee_id', self::EMPLOYEE_INDEX);
            });

            return;
        }

        $this->validateExistingTable();
        $this->assertNoDuplicatePairs();
        $this->addMissingForeignKeys();

        if (! Schema::hasIndex(self::TABLE, ['staff_group_id', 'employee_id'], 'unique')) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->unique(['staff_group_id', 'employee_id'], self::UNIQUE_INDEX);
            });
        }

        if (! Schema::hasIndex(self::TABLE, ['employee_id'])) {
            Schema::table(self::TABLE, function (Blueprint $table): void {
                $table->index('employee_id', self::EMPLOYEE_INDEX);
            });
        }
    }

    public function down(): void
    {
        // This table may have existed before this migration. Keep rollback non-destructive.
    }

    private function validateExistingTable(): void
    {
        $columns = collect(Schema::getColumns(self::TABLE))->keyBy('name');
        $required = ['id', 'staff_group_id', 'employee_id', 'created_at', 'updated_at'];

        foreach ($required as $column) {
            if (! $columns->has($column)) {
                throw new RuntimeException("La tabla ".self::TABLE." existe, pero falta la columna {$column}.");
            }
        }

        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('La reconciliación de staff_group_helpers solo admite MySQL y SQLite.');
        }

        foreach (['id', 'staff_group_id', 'employee_id'] as $column) {
            $definition = $columns->get($column);
            $typeName = strtolower((string) ($definition['type_name'] ?? ''));
            $type = strtolower((string) ($definition['type'] ?? ''));

            $valid = $driver === 'mysql'
                ? $typeName === 'bigint' && str_contains($type, 'unsigned')
                : $typeName === 'integer' || $type === 'integer';

            if (! $valid || $definition['nullable']) {
                throw new RuntimeException("La columna {$column} de ".self::TABLE.' no tiene el tipo o nulabilidad esperados.');
            }
        }

        foreach (['created_at', 'updated_at'] as $column) {
            $definition = $columns->get($column);
            $typeName = strtolower((string) ($definition['type_name'] ?? ''));
            if (! in_array($typeName, ['timestamp', 'datetime'], true) || ! $definition['nullable']) {
                throw new RuntimeException("La columna {$column} de ".self::TABLE.' no tiene un tipo temporal compatible.');
            }
        }
    }

    private function assertNoDuplicatePairs(): void
    {
        $duplicate = DB::table(self::TABLE)
            ->select('staff_group_id', 'employee_id')
            ->groupBy('staff_group_id', 'employee_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(
                'No se puede agregar la restricción UNIQUE: existen pares staff_group_id/employee_id duplicados.'
            );
        }
    }

    private function addMissingForeignKeys(): void
    {
        $foreignKeys = Schema::getForeignKeys(self::TABLE);
        $hasStaffGroupForeign = $this->hasForeignKey($foreignKeys, 'staff_group_id', 'staff_groups', 'cascade');
        $hasEmployeeForeign = $this->hasForeignKey($foreignKeys, 'employee_id', 'employees', 'restrict');

        if ($hasStaffGroupForeign && $hasEmployeeForeign) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table) use ($hasStaffGroupForeign, $hasEmployeeForeign): void {
            if (! $hasStaffGroupForeign) {
                $table->foreign('staff_group_id', 'staff_group_helpers_staff_group_id_foreign')
                    ->references('id')->on('staff_groups')->cascadeOnDelete();
            }

            if (! $hasEmployeeForeign) {
                $table->foreign('employee_id', 'staff_group_helpers_employee_id_foreign')
                    ->references('id')->on('employees')->restrictOnDelete();
            }
        });
    }

    private function hasForeignKey(array $foreignKeys, string $column, string $table, string $onDelete): bool
    {
        foreach ($foreignKeys as $foreignKey) {
            if (($foreignKey['columns'] ?? []) === [$column]
                && ($foreignKey['foreign_table'] ?? null) === $table
                && strtolower((string) ($foreignKey['on_delete'] ?? '')) === $onDelete) {
                return true;
            }
        }

        return false;
    }
};
