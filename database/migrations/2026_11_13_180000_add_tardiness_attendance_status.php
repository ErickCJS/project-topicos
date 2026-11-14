<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite stores enum columns as varchar and needs no alteration.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE attendances MODIFY status ENUM('Presente', 'Ausente', 'Tardanza') NOT NULL");
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('attendances')->where('status', 'Tardanza')->update(['status' => 'Presente']);
            DB::statement("ALTER TABLE attendances MODIFY status ENUM('Presente', 'Ausente') NOT NULL");
        }
    }
};
