<?php

namespace App\Http\Responses;

use App\Services\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Throwable;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private UserPermissionService $permissions)
    {
    }

    public function toResponse($request)
    {
        $user = $request->user();
        if ($this->permissions->isAdministrator($user)) {
            $request->session()->forget('url.intended');
            $destination = route('admin.home');
        } else {
            $destination = $this->authorizedIntendedUrl($request) ?? $this->permissions->firstAuthorizedListingUrl($user);
            $destination ??= route('access.restricted');
        }

        return $request->wantsJson()
            ? response()->json(['redirect' => $destination])
            : redirect()->to($destination);
    }

    private function authorizedIntendedUrl(Request $request): ?string
    {
        $url = $request->session()->pull('url.intended');
        if (!is_string($url) || $url === '') return null;

        $parts = parse_url($url);
        if ($parts === false || (isset($parts['host']) && strcasecmp($parts['host'], $request->getHost()) !== 0)) return null;
        $path = $parts['path'] ?? '';
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) return null;

        try {
            $matched = Route::getRoutes()->match(Request::create($path, 'GET'));
        } catch (Throwable) {
            return null;
        }

        $routeName = $matched->getName();
        if (!$routeName || str_starts_with($routeName, 'users.') || str_starts_with($routeName, 'user-types.') || !$this->permissions->allowsRoute($request->user(), $routeName)) return null;

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        return url($path).$query;
    }
}
