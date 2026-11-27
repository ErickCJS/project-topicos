<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Permission;
use App\Models\PermissionRoute;
use App\Models\User;
use Illuminate\Support\Facades\Route;

class UserPermissionService
{
    public function allows(?User $user, string $permission): bool
    {
        if (!$user || !$user->is_active) return false;
        return $this->isAdministrator($user) || $user->permissions()->where('slug', $permission)->exists();
    }

    public function allowsAnyModule(?User $user, array $modules): bool
    {
        if (!$user || !$user->is_active) return false;
        if ($this->isAdministrator($user)) return true;
        return $user->permissions()->where(function ($query) use ($modules) {
            foreach ($modules as $module) $query->orWhere('slug', 'like', $module.'.%');
        })->exists();
    }

    public function isAdministrator(?User $user): bool
    {
        return $user !== null && $user->is_active && (bool) $user->is_admin;
    }

    public function allowsRoute(?User $user, string $routeName): bool
    {
        $permission = config('permissions.route_map', [])[$routeName] ?? null;
        if (!$permission) {
            foreach (config('permissions.catalog', []) as $children) {
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

    public function firstAuthorizedListingUrl(User $user): ?string
    {
        $modules = Module::with(['children.permissions.routes'])->whereNull('parent_id')->orderBy('sort_order')->orderBy('id')->get();
        foreach ($modules as $module) {
            foreach ($module->children as $child) {
                $permission = $child->permissions->firstWhere('slug', $child->slug.'.view');
                if (!$permission || !$this->allows($user, $permission->slug)) continue;
                $routeName = $permission->routes->pluck('route_name')->first(fn ($name) => str_ends_with($name, '.index') && Route::has($name));
                if (!$routeName) {
                    foreach (config('permissions.catalog', []) as $children) {
                        if (isset($children[$child->slug])) {
                            $routeName = $children[$child->slug][1].'.index';
                            break;
                        }
                    }
                }
                if ($routeName && Route::has($routeName) && $routeName !== 'admin.home') return route($routeName);
            }
        }
        return $this->allows($user, 'dashboard.view') && Route::has('admin.dashboard') ? route('admin.dashboard') : null;
    }

    public function permissions(User $user): array
    {
        $query = Permission::with('module.parent')->orderBy('slug');
        return ($this->isAdministrator($user) ? $query->get() : $user->permissions()->with('module.parent')->orderBy('slug')->get())->toArray();
    }
}
