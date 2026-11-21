<?php

namespace App\Http\Middleware;

use App\Models\Citizen;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCitizen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof Citizen) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para acceder al portal ciudadano.',
            ], 403);
        }

        return $next($request);
    }
}
