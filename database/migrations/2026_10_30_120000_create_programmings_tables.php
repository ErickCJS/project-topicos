<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programmings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('employees')->restrictOnDelete();
            $table->date('date')->index();
            $table->string('status', 20)->default('Programado');
            $table->text('notes')->nullable();
            // Snapshots protect historical routes from subsequent template edits.
            $table->json('snapshot');
            $table->string('group_date_key', 100)->nullable()->unique();
            $table->string('vehicle_date_key', 100)->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('finished_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('programming_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programming_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('shift_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->string('role', 15);
            $table->unique(['employee_id', 'date', 'shift_id'], 'programming_employee_date_shift_unique');
        });
        Schema::create('programming_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programming_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programming_changes');
        Schema::dropIfExists('programming_employees');
        Schema::dropIfExists('programmings');
    }
};
