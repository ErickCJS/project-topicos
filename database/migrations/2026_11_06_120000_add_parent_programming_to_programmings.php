<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programmings', function (Blueprint $table) {
            $table->foreignId('parent_programming_id')->nullable()->after('id')
                ->constrained('programmings')->restrictOnDelete();
            $table->unique('parent_programming_id', 'programmings_one_direct_child_unique');
        });
    }

    public function down(): void
    {
        Schema::table('programmings', function (Blueprint $table) {
            $table->dropUnique('programmings_one_direct_child_unique');
            $table->dropForeign(['parent_programming_id']);
            $table->dropColumn('parent_programming_id');
        });
    }
};
