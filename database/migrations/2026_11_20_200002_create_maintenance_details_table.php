<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_schedule_id')
                ->constrained('maintenance_schedules')
                ->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->text('observation')->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->enum('status', ['Pendiente', 'Realizado', 'No realizado'])
                ->default('Pendiente');
            $table->timestamps();

            $table->unique(
                ['maintenance_schedule_id', 'scheduled_date'],
                'maintenance_details_schedule_date_unique'
            );
            $table->index(['scheduled_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_details');
    }
};
