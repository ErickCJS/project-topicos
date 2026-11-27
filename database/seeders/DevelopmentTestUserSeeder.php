<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevelopmentTestUserSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        $userType = UserType::firstOrCreate(
            ['name' => 'Usuario de prueba'],
            [
                'description' => 'Cuenta destinada a pruebas y desarrollo.',
            ]
        );

        $user = User::where('email', 'user@gmail.com')->first();

        if (!$user) {
            $dni = '99999999';

            while (User::where('dni', $dni)->exists()) {
                $dni = (string) (((int) $dni - 1) ?: 99999999);
            }

            $user = User::create([
                'name' => 'Usuario',
                'last_name' => 'De prueba',
                'dni' => $dni,
                'email' => 'user@gmail.com',
                'password' => Hash::make('Password123'),
                'is_admin' => false,
                'is_active' => true,
                'user_type_id' => $userType->id,
            ]);
        } else {
            $updates = [];

            if ($user->is_admin) {
                $updates['is_admin'] = false;
            }

            if (!$user->user_type_id) {
                $updates['user_type_id'] = $userType->id;
            }

            if ($updates) {
                $user->update($updates);
            }
        }

        $permissions = Permission::whereIn('slug', [
            'dashboard.view',
            'vehiculos.view',
            'vehiculos.details',
        ])->pluck('id');

        $user->permissions()->sync($permissions);
    }
}
