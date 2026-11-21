<?php

use App\Http\Controllers\Api\DriverAuthController;
use App\Http\Controllers\Api\DriverProgrammingController;
use App\Http\Controllers\Api\DriverLocationController;
use App\Http\Controllers\Api\CitizenAuthController;
use App\Http\Controllers\Api\CitizenProfileController;
use App\Http\Controllers\Api\CitizenZoneController;
use App\Http\Controllers\Api\CitizenProgrammingController;
use App\Http\Controllers\Api\CitizenTrackingController;
use App\Http\Controllers\Api\CitizenHistoryController;
use App\Http\Controllers\Api\CitizenRatingController;
use App\Http\Middleware\EnsureCitizen;
use App\Http\Middleware\EnsureEmployee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    if (! $request->user() instanceof User) {
        return response()->json([
            'success' => false,
            'message' => 'No autorizado para acceder a este recurso.',
        ], 403);
    }

    return $request->user();
})->middleware(['auth:sanctum', App\Http\Middleware\EnsureActiveUser::class]);

Route::middleware(['auth:sanctum', App\Http\Middleware\EnsureActiveUser::class])
    ->get('/me/access', function (
        Request $request,
        App\Services\UserPermissionService $service
    ) {
        $user = $request->user();

        if (!($user instanceof \App\Models\User)) {
            abort(403, 'No autorizado para acceder a este recurso.');
        }

        $permissions = collect($service->permissions($user));

        return response()->json([
            'is_admin' => (bool) $user->is_admin,
            'user_type_label' => $user->user_type_label,
            'modulos' => $permissions
                ->groupBy(fn ($permission) =>
                    $permission['module']['parent']['slug']
                    ?? $permission['module']['slug']
                )
                ->map(fn ($items, $module) => [
                    'identificador' => $module,
                    'submodulos' => $items
                        ->groupBy('module.slug')
                        ->map(fn ($permissions, $slug) => [
                            'identificador' => $slug,
                            'permisos' => $permissions->pluck('slug')->values(),
                        ])
                        ->values(),
                ])
                ->values(),
            'permisos' => $permissions->pluck('slug')->values(),
        ]);
    })->name('api.me.access');

// API para aplicación móvil Flutter
Route::post('/conductor/login', [DriverAuthController::class, 'login'])
    ->middleware('throttle:5,1');

Route::prefix('v1/ciudadano')->group(function () {
    Route::post('/register', [CitizenAuthController::class, 'register'])
        ->middleware('throttle:5,1');
    Route::post('/login', [CitizenAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', EnsureCitizen::class])->group(function () {
        Route::post('/logout', [CitizenAuthController::class, 'logout']);
        Route::get('/perfil', [CitizenProfileController::class, 'show']);
        Route::get('/zonas/actual', [CitizenZoneController::class, 'current']);
        Route::post('/zonas/detectar', [CitizenZoneController::class, 'detect']);
        Route::post('/zonas/confirmar', [CitizenZoneController::class, 'confirm']);
        Route::get('/programaciones', [CitizenProgrammingController::class, 'index']);
        Route::get('/programaciones/{id}', [CitizenProgrammingController::class, 'show'])
            ->whereNumber('id');
        // Flutter puede consultar aproximadamente cada 15 segundos; el backend limita esta ruta de forma independiente.
        Route::get('/programaciones/{id}/ubicacion', [CitizenTrackingController::class, 'show'])
            ->whereNumber('id')
            ->middleware('throttle:citizen-tracking');
        Route::get('/historial', [CitizenHistoryController::class, 'index']);
        Route::get('/historial/{id}', [CitizenHistoryController::class, 'show'])
            ->whereNumber('id');
        Route::post('/calificaciones', [CitizenRatingController::class, 'store']);
        Route::get('/calificaciones', [CitizenRatingController::class, 'index']);
        Route::get('/calificaciones/{id}', [CitizenRatingController::class, 'show'])
            ->whereNumber('id');
    });
});


Route::middleware(['auth:sanctum', EnsureEmployee::class])->group(function () {
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

    // Consultar todos los puntos GPS aceptados de un recorrido (activo o finalizado).
    Route::get('/conductor/programaciones/{programming}/ubicaciones', [
        DriverLocationController::class,
        'index'
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
