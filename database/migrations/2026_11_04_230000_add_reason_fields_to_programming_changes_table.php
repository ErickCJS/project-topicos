<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programming_changes', function (Blueprint $table) {
            $table->foreignId('reason_id')->nullable()->after('user_id')->constrained('reason')->nullOnDelete();
            $table->text('reason_detail')->nullable()->after('reason_id');
        });
    }

    public function down(): void
    {
        Schema::table('programming_changes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reason_id');
            $table->dropColumn('reason_detail');
        });
    }
};