<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable();
            $table->string('dni', 8)->nullable()->unique();
            $table->date('birthdate')->nullable();
            $table->string('phone')->nullable();
            $table->string('license')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('user_type_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_type_id');
            $table->dropUnique(['dni']);
            $table->dropColumn(['last_name', 'dni', 'birthdate', 'phone', 'license', 'address', 'is_active']);
        });
    }
};
