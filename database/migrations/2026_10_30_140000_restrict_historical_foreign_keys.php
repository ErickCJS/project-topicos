<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $foreignKeys = [
        ['attendances', 'employee_id', 'attendances_employee_id_foreign', 'employees'],
        ['attendances', 'shift_id', 'attendances_shift_id_foreign', 'shifts'],
        ['contracts', 'employee_id', 'contracts_employee_id_foreign', 'employees'],
        ['vacations', 'employee_id', 'vacations_employee_id_foreign', 'employees'],
        ['employees', 'employee_type_id', 'employees_employee_type_id_foreign', 'employee_types'],
    ];

    public function up(): void
    {
        foreach ($this->foreignKeys as [$tableName, $column, $constraint, $referencedTable]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $constraint, $referencedTable) {
                $table->dropForeign([$column]);

                $table->foreign($column, $constraint)
                    ->references('id')
                    ->on($referencedTable)
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->foreignKeys as [$tableName, $column, $constraint, $referencedTable]) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $constraint, $referencedTable) {
                $table->dropForeign([$column]);

                $table->foreign($column, $constraint)
                    ->references('id')
                    ->on($referencedTable)
                    ->cascadeOnDelete();
            });
        }
    }
};