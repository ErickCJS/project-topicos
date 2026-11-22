<?php

namespace App\Providers;

use App\Services\UserPermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::define('permission', fn ($user, string $permission) => app(UserPermissionService::class)->allows($user, $permission));
        Gate::define('module', fn ($user, ...$modules) => app(UserPermissionService::class)->allowsAnyModule($user, $modules));
    }
}
