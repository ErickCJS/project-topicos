<?php

use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverProgrammingController;
use App\Http\Controllers\Api\DriverLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// API para aplicación móvil Flutter
Route::post('/conductor/login', [DriverAuthController::class, 'login'])
    ->middleware('throttle:5,1');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/conductor/programaciones', [
        DriverAuthController::class,
        'programaciones'
    ]);
    // Solamente las programaciones de hoy
    Route::get('/conductor/programaciones/hoy', [
        DriverAuthController::class,
        'programacionesHoy'
    ]);
    // Iniciar recorrido únicamente dentro del horario configurado del turno.
    Route::post('/conductor/programaciones/{programming}/iniciar', [
        DriverProgrammingController::class,
        'start'
    ]);

    // Finalizar un recorrido que ya fue iniciado, sin límite de horario.
    Route::post('/conductor/programaciones/{programming}/finalizar', [
        DriverProgrammingController::class,
        'finish'
    ]);

    // Registrar ubicaciones GPS del conductor mientras el recorrido esté abierto.
    Route::post('/conductor/programaciones/{programming}/ubicacion', [
        DriverLocationController::class,
        'store'
    ])->middleware('throttle:30,1');

    // Detalle de una programación específica del conductor autenticado.
    Route::get('/conductor/programaciones/{programming}', [
        DriverProgrammingController::class,
        'show'
    ]);
});
