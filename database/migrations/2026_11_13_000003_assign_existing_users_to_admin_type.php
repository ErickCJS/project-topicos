<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $adminId = DB::table('user_types')->where('slug', 'administrador')->value('id');
        if (!$adminId) {
            $adminId = DB::table('user_types')->insertGetId([
                'name' => 'Administrador', 'slug' => 'administrador', 'is_admin' => true,
                'is_system' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $test = DB::table('users')->where('email', 'user@gmail.com')->first(['id']);
        $testId = null;
        if ($test) {
            $testId = DB::table('user_types')->where('slug', 'usuario-de-prueba')->value('id');
            if (!$testId) {
                $testId = DB::table('user_types')->insertGetId([
                    'name' => 'Usuario de prueba', 'slug' => 'usuario-de-prueba', 'is_admin' => false,
                    'is_system' => true, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        DB::table('user_types')->where('id', $adminId)->update(['name' => 'Administrador', 'is_admin' => true, 'is_system' => true, 'is_active' => true, 'updated_at' => $now]);
        if ($testId) DB::table('user_types')->where('id', $testId)->update(['name' => 'Usuario de prueba', 'is_admin' => false, 'is_system' => true, 'is_active' => true, 'updated_at' => $now]);
        DB::table('users')->where('email', '<>', 'user@gmail.com')->update(['user_type_id' => $adminId]);
        if ($test) DB::table('users')->where('id', $test->id)->update(['user_type_id' => $testId]);
    }

    public function down(): void {}
};
