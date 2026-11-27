<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmings', function (Blueprint $table) {
            $table->foreignId('ended_by')
                ->nullable()
                ->after('started_at')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programmings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ended_by');
        });
    }
};
