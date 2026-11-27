<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        // Every successfully authenticated user lands on the system welcome page.
        // Module access is presented there according to the user's permissions.
        $request->session()->forget('url.intended');

        $destination = route('admin.home');

        return $request->wantsJson()
            ? response()->json(['redirect' => $destination])
            : redirect()->to($destination);
    }
}
