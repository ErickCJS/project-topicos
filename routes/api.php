<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', App\Http\Middleware\EnsureActiveUser::class]);

Route::middleware(['auth:sanctum', App\Http\Middleware\EnsureActiveUser::class])->get('/me/access', function (Request $request, App\Services\UserPermissionService $service) {
    $user = $request->user()->load('userType');
    return response()->json([
        'tipo' => $user->userType ? ['id' => $user->userType->id, 'nombre' => $user->userType->name, 'slug' => $user->userType->slug] : null,
        'modulos' => collect($service->permissions($user))->groupBy(fn ($permission) => $permission['module']['parent']['slug'] ?? $permission['module']['slug'])->map(fn ($items, $module) => [
            'identificador' => $module,
            'submodulos' => $items->groupBy('module.slug')->map(fn ($permissions, $slug) => ['identificador' => $slug, 'permisos' => $permissions->pluck('slug')->values()])->values(),
        ])->values(),
        'permisos' => collect($service->permissions($user))->pluck('slug')->values(),
    ]);
})->name('api.me.access');
