<?php

namespace App\Http\Controllers\Api;

use App\Models\admin\Programming;
use App\Models\admin\StaffGroup;
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

        // 1. Buscar exclusivamente conductores activos.
        $employee = Employee::where('email', $credentials['email'])
            ->where('employee_type_id', 1)
            ->where('status', 'Activo')
            ->first();

        // 2. Validar existencia y credenciales.
        if (!$employee || !Hash::check(
            $credentials['password'],
            $employee->password
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas.'
            ], 401);
        }

        // 3. Validar contrato vigente.
        if (!$employee->hasVigenteContract()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un contrato vigente. Comunícate con administración.'
            ], 403);
        }

        // 4. Generar token Sanctum.
        $token = $employee->createToken('flutter-driver')->plainTextToken;

        // 5. Respuesta exitosa.
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


    public function programaciones(Request $request)
    {
        // Identificar al conductor mediante el token Sanctum.
        $employee = $request->user();

        // Verificar que sea un conductor activo.
        if (
            !$employee instanceof Employee ||
            (int) $employee->employee_type_id !== 1 ||
            $employee->status !== 'Activo'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes autorización para consultar estas programaciones.'
            ], 403);
        }

        // Verificar contrato vigente.
        if (!$employee->hasVigenteContract()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un contrato vigente.'
            ], 403);
        }

        // Obtener exclusivamente las programaciones del conductor.
        $programaciones = Programming::query()
            ->where('driver_id', $employee->id)
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Obtener los grupos relacionados para consultar sus días laborales.
        $groupIds = $programaciones
            ->pluck('staff_group_id')
            ->filter()
            ->unique()
            ->values();

        $groups = StaffGroup::with('days')
            ->whereIn('id', $groupIds)
            ->get()
            ->keyBy('id');

        // Equivalencia de días ISO: lunes = 1, domingo = 7.
        $dayNames = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        // Preparar información para Flutter.
        $data = $programaciones->map(function ($programacion) use ($groups, $dayNames) {

            $snapshot = $programacion->snapshot ?? [];

            $group = $groups->get($programacion->staff_group_id);

            $days = $group
                ? $group->days
                ->pluck('day')
                ->map(fn($day) => (int) $day)
                ->sort()
                ->values()
                ->map(fn($day) => $dayNames[$day] ?? null)
                ->filter()
                ->values()
                ->all()
                : [];

            return [
                'id' => $programacion->id,
                'date' => $programacion->date?->format('Y-m-d'),

                'zone' => $snapshot['zone'] ?? null,

                'vehicle' => $snapshot['vehicle'] ?? null,
                'plate' => $snapshot['plate'] ?? null,

                'shift' => $snapshot['shift'] ?? null,
                'hours' => $snapshot['hours'] ?? null,

                'days' => $days,

                'status' => $programacion->status,
                'notes' => $programacion->notes,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Programaciones obtenidas correctamente.',
            'employee' => [
                'id' => $employee->id,
                'full_name' => $employee->first_name . ' ' . $employee->last_name,
                'dni' => $employee->dni,
            ],
            'total' => $data->count(),
            'data' => $data,
        ], 200);
    }
}
