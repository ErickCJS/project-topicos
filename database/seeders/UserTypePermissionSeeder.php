<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $administrativeType = UserType::firstOrCreate(
            ['name' => 'Administrativo'],
            [
                'description' => 'Plantilla administrativa inicial con todos los permisos del sistema. Puedes personalizarla desde Gestión de usuarios.',
            ]
        );

        // Solo se inicializa con acceso completo cuando se crea por primera vez.
        // Las personalizaciones posteriores del administrador se respetan.
        if ($administrativeType->wasRecentlyCreated) {
            $administrativeType->permissions()->sync(
                Permission::query()->pluck('id')->all()
            );
        }
    }
}
