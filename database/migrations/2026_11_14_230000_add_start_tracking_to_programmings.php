<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmings', function (Blueprint $table) {
            $table->foreignId('started_by')
                ->nullable()
                ->after('driver_id')
                ->constrained('employees')
                ->nullOnDelete();

            $table->timestamp('started_at')->nullable()->after('started_by');

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
            $table->dropConstrainedForeignId('started_by');
            $table->dropColumn('started_at');
        });
    }
};
