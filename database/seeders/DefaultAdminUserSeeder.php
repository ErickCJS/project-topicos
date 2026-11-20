<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultAdminUserSeeder extends Seeder
{
    private const EMAIL = 'administrador@rsu.local';
    private const PASSWORD = 'Admin@RSU2026!';

    public function run(): void
    {
        $administratorType = UserType::firstOrCreate(
            ['name' => 'Administrador'],
            [
                'description' => 'Usuario con acceso administrativo total al sistema.',
            ]
        );

        $user = User::firstOrNew([
            'email' => self::EMAIL,
        ]);

        if (!$user->exists) {
            $user->forceFill([
                'name' => 'Administrador',
                'last_name' => 'RSU',
                'dni' => $this->availableDni(),
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]);
        }

        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }

        if (!$user->user_type_id) {
            $user->user_type_id = $administratorType->id;
        }

        $user->forceFill([
            'is_admin' => true,
            'is_active' => true,
        ])->save();
    }

    private function availableDni(): string
    {
        $dni = 99999999;

        while (User::where('dni', (string) $dni)->exists()) {
            $dni = $dni > 1 ? $dni - 1 : 99999999;
        }

        return str_pad((string) $dni, 8, '0', STR_PAD_LEFT);
    }
}
