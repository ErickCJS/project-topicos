<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citizen_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('citizen_id')->constrained('citizens')->restrictOnDelete();
            $table->foreignId('programming_id')->constrained('programmings')->restrictOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->string('comment', 500)->nullable();
            $table->timestamps();

            $table->unique(['citizen_id', 'programming_id'], 'citizen_ratings_citizen_programming_unique');
            $table->index(['citizen_id', 'created_at'], 'citizen_ratings_citizen_created_at_index');
            $table->index('programming_id', 'citizen_ratings_programming_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citizen_ratings');
    }
};
