<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverAuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        // Buscar exclusivamente conductores activos.
        $employee = Employee::where('email', $credentials['email'])
            ->where('employee_type_id', 1)
            ->where('status', 'Activo')
            ->first();

        // Validar credenciales.
        if (!$employee || !Hash::check(
            $credentials['password'],
            $employee->password
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.'
            ], 401);
        }

        // Generar token Sanctum.
        $token = $employee->createToken('flutter-driver')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'token_type' => 'Bearer',
            'access_token' => $token,
            'employee' => [
                'id' => $employee->id,
                'email' => $employee->email,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'full_name' => $employee->first_name . ' ' . $employee->last_name,
                'license' => $employee->license,
                'phone' => $employee->phone,
                'profile_photo_path' => $employee->profile_photo_path,
            ]
        ], 200);
    }
}
