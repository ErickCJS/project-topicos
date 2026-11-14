<?php

namespace App\Http\Middleware;

use App\Models\PermissionRoute;
use App\Services\UserPermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdminRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->getAttribute('is_active') === false) abort(403);
        $permissions = app(UserPermissionService::class);
        if ($permissions->isAdministrator($user)) return $next($request);
        if (!Schema::hasTable('user_types') || !Schema::hasColumn('users', 'user_type_id')) {
            $routeName = $request->route()?->getName();
            if ($routeName && $permissions->allowsRoute($user, $routeName)) return $next($request);
            abort(403);
        }
        $routeName = $request->route()?->getName();
        if ($routeName && (str_starts_with($routeName, 'users.') || str_starts_with($routeName, 'user-types.'))) abort(403);
        $permission = $routeName ? PermissionRoute::with('permission')->where('route_name', $routeName)->first()?->permission : null;
        if (!$permission || !$permissions->allows($user, $permission->slug)) abort(403);
        return $next($request);
    }
}
