<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programming_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programming_id')
                ->constrained('programmings')
                ->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->timestamp('recorded_at');

            $table->timestamps();

            $table->index(['programming_id', 'id'], 'programming_locations_programming_order_idx');
            $table->index(['employee_id', 'recorded_at'], 'programming_locations_employee_recorded_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programming_locations');
    }
};
