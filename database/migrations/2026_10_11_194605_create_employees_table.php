<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_type_id')->constrained()->cascadeOnDelete();
            $table->char('dni', 8)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birthdate');
            $table->string('license', 20)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('email', 150)->unique();
            $table->string('password', 255);
            $table->enum('status', ['Activo', 'Inactivo'])->default('Activo');
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
