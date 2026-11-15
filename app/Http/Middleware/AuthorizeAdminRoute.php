<?php

namespace App\Http\Middleware;

use App\Models\PermissionRoute;
use App\Services\UserPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeAdminRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || $user->getAttribute('is_active') === false) abort(403);
        $permissions = app(UserPermissionService::class);
        if ($permissions->isAdministrator($user)) return $next($request);

        // Si no existe ningún módulo de entrada autorizado, mostrar una pantalla
        // explicativa en lugar de dejar al usuario frente a un error 403.
        if ($permissions->firstAuthorizedListingUrl($user) === null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu cuenta todavía no tiene módulos autorizados para ingresar al sistema.',
                    'redirect' => route('access.restricted'),
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('access.restricted');
        }

        $routeName = $request->route()?->getName();
        if ($routeName && str_starts_with($routeName, 'users.')) abort(403);
        $permission = $routeName ? PermissionRoute::with('permission')->where('route_name', $routeName)->first()?->permission : null;
        if (!$permission || !$permissions->allows($user, $permission->slug)) abort(403);
        return $next($request);
    }
}
