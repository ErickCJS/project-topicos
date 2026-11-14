<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->getAttribute('is_active') === false) {
            if (!$request->expectsJson() && auth()->guard('web')->check()) auth()->guard('web')->logout();
            abort(403);
        }
        return $next($request);
    }
}
