<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            DefaultAdminUserSeeder::class,
            DevelopmentTestUserSeeder::class,
        ]);
        $this->call([
            EmployeeTypeSeeder::class,
            ShiftSeeder::class,
            EmployeeSeeder::class,
            ContractSeeder::class,
            BulkProgrammingStaffSeeder::class,
            OperationalStaffSeeder::class,
            VacationSeeder::class,
            HolidaySeeder::class,
            AttendanceSeeder::class,
            CatalogSeeder::class,
            ReasonSeeder::class,
        ]);

        // Genera el catálogo de permisos desde la configuración de rutas antes
        // de inicializar la plantilla administrativa.
        Artisan::call('permissions:sync-routes');
        $this->call(UserTypePermissionSeeder::class);
    }
}
