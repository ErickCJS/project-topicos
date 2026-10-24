<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('year');
            $table->date('end_date')->nullable()->after('start_date');
            $table->index(['employee_id', 'year'], 'vacations_employee_year_index');
            $table->index(['employee_id', 'start_date', 'end_date'], 'vacations_employee_period_index');
        });
    }

    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            $table->dropIndex('vacations_employee_year_index');
            $table->dropIndex('vacations_employee_period_index');
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
