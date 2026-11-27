<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\UserType;
use Illuminate\Database\Seeder;

class UserTypePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // DefaultAdminUserSeeder already creates this type in a normal install.
        $administratorType = UserType::firstOrCreate(
            ['name' => 'Administrador'],
            [
                'description' => 'Plantilla con todos los permisos granulares del sistema. Puedes personalizarla desde Gestión de usuarios.',
            ]
        );

        // Initialize the template only when it has not been configured yet.
        // Existing customizations are preserved on subsequent seeder executions.
        $permissionIds = Permission::query()->pluck('id')->all();

        // If the permission catalog has not been synchronized yet, leave the
        // template pending so this seeder can safely be run again afterwards.
        if (! $administratorType->permissions_configured && $permissionIds !== []) {
            $administratorType->permissions()->sync($permissionIds);

            $administratorType->forceFill([
                'permissions_configured' => true,
            ])->save();
        }
    }
}
