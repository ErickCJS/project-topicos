<?php

namespace App\Console\Commands;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionRoute;
use App\Models\UserType;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncPermissionRoutes extends Command
{
    protected $signature = 'permissions:sync-routes';
    protected $description = 'Sincroniza el catálogo de permisos con las rutas administrativas.';

    public function handle(): int
    {
        $unclassified = [];
        foreach (config('permissions.catalog') as $groupName => $children) {
            $parent = Module::updateOrCreate(['slug' => Str::slug($groupName)], ['name' => $groupName, 'parent_id' => null]);
            foreach ($children as $moduleSlug => [$moduleName, $prefix, $keys]) {
                $module = Module::updateOrCreate(['slug' => $moduleSlug], ['name' => $moduleName, 'parent_id' => $parent->id]);
                foreach ($keys as $key) {
                    Permission::updateOrCreate(['slug' => "$moduleSlug.$key"], ['module_id' => $module->id, 'name' => config("permissions.actions.$key", ucfirst($key))]);
                }
            }
        }

        $routeMap = config('permissions.route_map');
        $prefixes = [];
        foreach (config('permissions.catalog') as $children) {
            foreach ($children as $slug => [$name, $prefix, $keys]) $prefixes[$prefix] = $slug;
        }
        foreach (app('router')->getRoutes() as $route) {
            $name = $route->getName();
            if (!$name || ($route->uri() !== 'admin' && !str_starts_with($route->uri(), 'admin/'))) continue;
            $permissionSlug = $routeMap[$name] ?? null;
            if (!$permissionSlug) {
                foreach ($prefixes as $prefix => $moduleSlug) {
                    if (!str_starts_with($name, $prefix.'.')) continue;
                    $tail = substr($name, strlen($prefix) + 1);
                    $verb = match ($tail) {
                        'index' => 'view', 'show' => 'details', 'create', 'store' => 'create', 'edit', 'update' => 'edit', 'destroy' => 'delete', default => null,
                    };
                    if ($verb) $permissionSlug = "$moduleSlug.$verb";
                    break;
                }
            }
            if (!$permissionSlug || !($permission = Permission::where('slug', $permissionSlug)->first())) {
                $unclassified[] = $name;
                continue;
            }
            PermissionRoute::updateOrCreate(['route_name' => $name], ['permission_id' => $permission->id]);
        }

        $adminType = UserType::firstOrCreate(['slug' => 'administrador'], ['name' => 'Administrador', 'is_admin' => true, 'is_system' => true, 'is_active' => true]);
        $adminType->forceFill(['name' => 'Administrador', 'is_admin' => true, 'is_system' => true, 'is_active' => true])->save();
        UserType::firstOrCreate(['slug' => 'pendiente-de-asignacion'], ['name' => 'Pendiente de asignación', 'is_system' => true, 'is_active' => true]);
        if (app()->environment(['local', 'testing'])) {
            $testType = UserType::firstOrCreate(['slug' => 'usuario-de-prueba'], ['name' => 'Usuario de prueba', 'is_admin' => false, 'is_system' => true, 'is_active' => true]);
            $testType->forceFill(['name' => 'Usuario de prueba', 'is_admin' => false, 'is_system' => true, 'is_active' => true])->save();
        }
        $this->info('Catálogo sincronizado.');
        if ($unclassified) {
            $this->warn('Rutas administrativas sin clasificar:');
            foreach ($unclassified as $routeName) $this->line($routeName);
        }
        return self::SUCCESS;
    }
}
