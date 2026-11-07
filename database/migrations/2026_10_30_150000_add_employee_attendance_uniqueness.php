<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This is the database-level last line of defense for concurrent requests.
        Schema::table('attendances', function (Blueprint $table) {
            $table->unique(
                ['employee_id', 'shift_id', 'date', 'type'],
                'attendances_employee_shift_date_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique('attendances_employee_shift_date_type_unique');
        });
    }
};
