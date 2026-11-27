<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programming_changes', function (Blueprint $table) {
            $table->uuid('operation_id')->nullable()->index();
            $table->string('reason')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('origin_programming_id')->nullable()->constrained('programmings')->restrictOnDelete();
            $table->foreignId('destination_programming_id')->nullable()->constrained('programmings')->restrictOnDelete();
            $table->json('resources')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('programming_changes', function (Blueprint $table) {
            $table->dropForeign(['origin_programming_id']);
            $table->dropForeign(['destination_programming_id']);
            $table->dropIndex('programming_changes_operation_id_index');
            $table->dropColumn(['operation_id', 'reason', 'description', 'origin_programming_id', 'destination_programming_id', 'resources']);
        });
    }
};
