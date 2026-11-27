<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function(){
            Route::middleware(['web', 'auth', 'active.user', 'admin.permission'])
            ->prefix('admin')
            ->group(base_path('routes/admin.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'active.user' => App\Http\Middleware\EnsureActiveUser::class,
            'admin.permission' => App\Http\Middleware\AuthorizeAdminRoute::class,
        ]);
        $middleware->redirectGuestsTo(fn (Request $request) =>
            $request->is('employee/*') || $request->is('employee')
                ? route('employee.login')
                : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
