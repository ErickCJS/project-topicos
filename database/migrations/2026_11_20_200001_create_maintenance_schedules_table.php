<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_id')
                ->constrained('maintenances')
                ->cascadeOnDelete();
            $table->foreignId('vehicle_id')
                ->constrained('vehicles')
                ->restrictOnDelete();
            $table->foreignId('responsible_id')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->enum('type', ['Preventivo', 'Limpieza', 'Reparación']);
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['vehicle_id', 'weekday']);
            $table->index(['maintenance_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_schedules');
    }
};
