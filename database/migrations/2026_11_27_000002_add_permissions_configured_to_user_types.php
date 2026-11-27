<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('user_types', 'permissions_configured')) {
            Schema::table('user_types', function (Blueprint $table) {
                $table->boolean('permissions_configured')->default(false)->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('user_types', 'permissions_configured')) {
            Schema::table('user_types', function (Blueprint $table) {
                $table->dropColumn('permissions_configured');
            });
        }
    }
};
