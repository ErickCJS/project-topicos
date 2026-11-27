<?php

namespace App\\Http\\Middleware;

use App\\Models\\PermissionRoute;
use App\\Services\\UserPermissionService;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class AuthorizeAdminRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->getAttribute('is_active') === false) {
            abort(403);
        }

        $permissions = app(UserPermissionService::class);

        if ($permissions->isAdministrator($user)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        // The welcome page is available to every authenticated active user.
        if ($routeName === 'admin.home') {
            return $next($request);
        }

        $adminOnlyRoutes = config('permissions.admin_only_routes', []);

        if (
            $routeName
            && in_array($routeName, $adminOnlyRoutes, true)
        ) {
            abort(403);
        }

        if ($permissions->firstAuthorizedListingUrl($user) === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu cuenta todavía no tiene módulos de entrada autorizados para ingresar al sistema.',
                    'redirect' => route('access.restricted'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('access.restricted');
        }

        if ($routeName && str_starts_with($routeName, 'users.')) {
            abort(403);
        }

        $permission = $routeName
            ? PermissionRoute::with('permission')
                ->where('route_name', $routeName)
                ->first()?->permission
            : null;

        // Prefer the synchronized database mapping, but fall back to the
        // permission catalog so a newly added route is still protected.
        $authorized = $permission
            ? $permissions->allows($user, $permission->slug)
            : ($routeName !== null && $permissions->allowsRoute($user, $routeName));

        if (!$authorized) {
            abort(403);
        }

        return $next($request);
    }
}
