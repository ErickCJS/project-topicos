<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class UserPermissionService
{
    public function allows(?User $user, string $permission): bool
    {
        if (!$user || $user->getAttribute('is_active') === false) return false;
        if ($this->isAdministrator($user)) return true;
        if (!Schema::hasTable('user_types') || !Schema::hasColumn('users', 'user_type_id')) return $this->isDevelopmentTestUser($user) && in_array($permission, ['dashboard.view', 'vehiculos.view', 'vehiculos.details'], true);
        return $user->userType?->is_active === true
            && $user->userType->permissions()->where('slug', $permission)->exists();
    }

    public function allowsAnyModule(?User $user, array $modules): bool
    {
        if (!$user || $user->getAttribute('is_active') === false) return false;
        if ($this->isAdministrator($user)) return true;
        if (!Schema::hasTable('user_types') || !Schema::hasColumn('users', 'user_type_id')) {
            foreach ($modules as $module) {
                foreach (['dashboard.view', 'vehiculos.view', 'vehiculos.details'] as $permission) {
                    if (str_starts_with($permission, $module.'.')) return true;
                }
            }
            return false;
        }
        $type = $user->userType;
        if (!$type || !$type->is_active) return false;

        return $type->permissions()->where(function ($query) use ($modules) {
            foreach ($modules as $module) $query->orWhere('slug', 'like', $module.'.%');
        })->exists();
    }

    public function isAdministrator(?User $user): bool
    {
        if (!$user || $user->getAttribute('is_active') === false) return false;
        $schemaReady = Schema::hasTable('user_types') && Schema::hasColumn('users', 'user_type_id');
        if (!$schemaReady) return !$this->isDevelopmentTestUser($user);
        return (bool) $user->userType?->is_admin;
    }

    public function allowsRoute(?User $user, string $routeName): bool
    {
        $permission = config('permissions.route_map', [])[$routeName] ?? null;
        if (!$permission) {
            foreach (config('permissions.catalog') as $children) {
                foreach ($children as $moduleSlug => [, $prefix, $keys]) {
                    if (!str_starts_with($routeName, $prefix.'.')) continue;
                    $tail = substr($routeName, strlen($prefix) + 1);
                    $action = match ($tail) {
                        'index' => 'view', 'show' => 'details', 'create', 'store' => 'create', 'edit', 'update' => 'edit', 'destroy' => 'delete', default => null,
                    };
                    if ($action && in_array($action, $keys, true)) $permission = "$moduleSlug.$action";
                    break 2;
                }
            }
        }
        return $permission ? $this->allows($user, $permission) : false;
    }

    private function isDevelopmentTestUser(User $user): bool
    {
        return app()->environment(['local', 'testing']) && strcasecmp($user->email, 'user@gmail.com') === 0;
    }

    public function permissions(User $user): array
    {
        if ($this->isAdministrator($user)) return Permission::with('module.parent')->orderBy('slug')->get()->toArray();
        return $user->userType?->permissions()->with('module.parent')->orderBy('slug')->get()->toArray() ?? [];
    }
}
