<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date')->index();
            $table->text('description')->nullable();
            $table->boolean('state')->default(true);
            $table->timestamps();
            $table->unsignedSmallInteger('year')
                ->storedAs('YEAR(date)');
            $table->unique(['name', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
