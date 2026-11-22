<?php

use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverProgrammingController;
use App\Http\Controllers\Api\DriverLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', App\Http\Middleware\EnsureActiveUser::class]);

Route::middleware(['auth:sanctum', App\Http\Middleware\EnsureActiveUser::class])->get('/me/access', function (Request $request, App\Services\UserPermissionService $service) {
    $user = $request->user();
    return response()->json([
        'is_admin' => (bool) $user->is_admin,
        'user_type_label' => $user->user_type_label,
        'modulos' => collect($service->permissions($user))->groupBy(fn ($permission) => $permission['module']['parent']['slug'] ?? $permission['module']['slug'])->map(fn ($items, $module) => [
            'identificador' => $module,
            'submodulos' => $items->groupBy('module.slug')->map(fn ($permissions, $slug) => ['identificador' => $slug, 'permisos' => $permissions->pluck('slug')->values()])->values(),
        ])->values(),
        'permisos' => collect($service->permissions($user))->pluck('slug')->values(),
    ]);
})->name('api.me.access');

// Endpoint temporal de diagnóstico: ejecuta SELECT * FROM contracts y devuelve tiempos, sin exponer los registros.
Route::get('/test-db-contracts', function () {
    $start = microtime(true);

    $contracts = DB::select('SELECT * FROM contracts');

    $queryTime = (microtime(true) - $start) * 1000;

    return response()->json([
        'status' => 'ok',
        'query' => 'SELECT * FROM contracts',
        'records' => count($contracts),
        'query_time_ms' => round($queryTime, 2),
    ]);
})->middleware('throttle:3,1');

// API para aplicación móvil Flutter
Route::post('/conductor/login', [DriverAuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/conductor/programaciones', [DriverAuthController::class, 'programaciones']);
    Route::get('/conductor/programaciones/hoy', [DriverAuthController::class, 'programacionesHoy']);
    Route::post('/conductor/programaciones/{programming}/iniciar', [DriverProgrammingController::class, 'start']);
    Route::post('/conductor/programaciones/{programming}/finalizar', [DriverProgrammingController::class, 'finish']);
    Route::get('/conductor/programaciones/{programming}/ubicaciones', [DriverLocationController::class, 'index']);
    Route::post('/conductor/programaciones/{programming}/ubicacion', [DriverLocationController::class, 'store'])
        ->middleware('throttle:30,1');
    Route::get('/conductor/programaciones/{programming}', [DriverProgrammingController::class, 'show']);
});
