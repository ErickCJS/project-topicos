<?php

namespace App\Http\Middleware;

use App\Models\admin\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployee
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Employee) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para acceder al portal del conductor.',
            ], 403);
        }

        return $next($request);
    }
}
