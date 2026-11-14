<?php

use App\Models\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminType = UserType::firstOrCreate(
            ['slug' => 'administrador'],
            ['name' => 'Administrador', 'is_admin' => true, 'is_system' => true, 'is_active' => true]
        );
        $adminType->forceFill(['name' => 'Administrador', 'is_admin' => true, 'is_system' => true, 'is_active' => true])->save();

        $testUser = DB::table('users')->where('email', 'user@gmail.com')->first(['id']);
        $testType = null;
        if ($testUser) {
            $testType = UserType::firstOrCreate(
                ['slug' => 'usuario-de-prueba'],
                ['name' => 'Usuario de prueba', 'is_admin' => false, 'is_system' => true, 'is_active' => true]
            );
            $testType->forceFill(['name' => 'Usuario de prueba', 'is_admin' => false, 'is_system' => true, 'is_active' => true])->save();
        }

        DB::transaction(function () use ($adminType, $testUser, $testType) {
            DB::table('users')->where('email', '<>', 'user@gmail.com')->update(['user_type_id' => $adminType->id]);
            if ($testUser && $testType) DB::table('users')->where('id', $testUser->id)->update(['user_type_id' => $testType->id]);
        });
    }

    public function down(): void
    {
    }
};
