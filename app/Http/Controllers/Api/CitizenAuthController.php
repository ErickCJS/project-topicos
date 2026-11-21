<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class CitizenAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'dni' => ['required', 'string', 'digits:8', 'unique:citizens,dni'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:citizens,email'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $citizen = Citizen::create([
            'dni' => $data['dni'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $token = $citizen->createToken('flutter-citizen')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Ciudadano registrado correctamente.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'citizen' => $this->profile($citizen),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $citizen = Citizen::where('email', $credentials['email'])->first();

        if (! $citizen || ! Hash::check($credentials['password'], $citizen->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $token = $citizen->createToken('flutter-citizen')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'citizen' => $this->profile($citizen),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($token = $request->bearerToken()) {
            PersonalAccessToken::findToken($token)?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    private function profile(Citizen $citizen): array
    {
        return [
            'id' => $citizen->id,
            'dni' => $citizen->dni,
            'first_name' => $citizen->first_name,
            'last_name' => $citizen->last_name,
            'email' => $citizen->email,
        ];
    }
}
