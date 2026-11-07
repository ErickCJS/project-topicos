<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programming_changes', function (Blueprint $table) {
            $table->dropForeign(['programming_id']);
            $table->foreign('programming_id', 'programming_changes_programming_id_foreign')
                ->references('id')
                ->on('programmings')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programming_changes', function (Blueprint $table) {
            $table->dropForeign(['programming_id']);
            $table->foreign('programming_id', 'programming_changes_programming_id_foreign')
                ->references('id')
                ->on('programmings')
                ->cascadeOnDelete();
        });
    }
};
