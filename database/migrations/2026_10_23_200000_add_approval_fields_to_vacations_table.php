<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            $table->string('status', 20)->default('Pendiente')->after('pending_days')->index();
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
        });

        // Existing rows already represented granted annual allocations.
        DB::table('vacations')->update(['status' => 'Aprobada']);
    }

    public function down(): void
    {
        Schema::table('vacations', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'rejection_reason', 'reviewed_at']);
        });
    }
};
