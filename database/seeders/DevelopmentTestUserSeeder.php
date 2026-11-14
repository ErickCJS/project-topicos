<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DevelopmentTestUserSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) return;
        $type = UserType::firstOrCreate(['slug' => 'usuario-de-prueba'], ['name' => 'Usuario de prueba', 'is_active' => true]);
        $type->forceFill(['name' => 'Usuario de prueba', 'is_admin' => false, 'is_system' => true, 'is_active' => true])->save();
        $user = User::where('email', 'user@gmail.com')->first();
        if (!$user) {
            $dni = '99999999';
            while (User::where('dni', $dni)->exists()) $dni = (string) (((int) $dni - 1) ?: 99999999);
            $user = User::create([
                'name' => 'Usuario', 'last_name' => 'De prueba', 'dni' => $dni, 'email' => 'user@gmail.com',
                'password' => Hash::make('Password123'), 'user_type_id' => $type->id, 'is_active' => true,
            ]);
        } else {
            DB::table('users')->where('id', $user->id)->update(['user_type_id' => $type->id]);
        }
        $permissions = Permission::whereIn('slug', ['dashboard.view', 'vehiculos.view', 'vehiculos.details'])->pluck('id');
        $type->permissions()->sync($permissions);
    }
}
