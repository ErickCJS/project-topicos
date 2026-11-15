<?php

namespace App\Http\Controllers\Api;

use App\Models\admin\Programming;
use App\Models\admin\StaffGroup;
use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use App\Models\admin\Attendance;
use Carbon\CarbonImmutable;

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
        //eliminamos tokens anteriores
        $employee->tokens()->delete();

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


    public function programacionesHoy(Request $request): JsonResponse
    {
        $employee = $request->user();

        // 1. Validar conductor activo.
        if (
            !$employee instanceof Employee ||
            (int) $employee->employee_type_id !== 1 ||
            $employee->status !== 'Activo'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes autorización para consultar esta información.'
            ], 403);
        }

        // 2. Validar contrato vigente.
        if (!$employee->hasVigenteContract()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un contrato vigente.'
            ], 403);
        }

        // 3. Obtener fecha actual de Perú.
        $today = today('America/Lima')->toDateString();

        // 4. Buscar solamente las programaciones de hoy.
        $programaciones = Programming::query()
            ->with([
                'employees' => fn($query) => $query->orderByPivot('id'),
                'vehicle',
                'shift',
            ])
            ->where('driver_id', $employee->id)
            ->whereDate('date', $today)
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->orderBy('id')
            ->get();

        // 5. Si no tiene programación hoy.
        if ($programaciones->isEmpty()) {
            return response()->json([
                'success' => true,
                'has_programming' => false,
                'message' => 'No tienes recorridos programados para hoy.',
                'date' => $today,
                'total' => 0,
                'data' => [],
            ], 200);
        }

        // 6. Obtener los integrantes de todos los recorridos.
        $employeeIds = $programaciones
            ->flatMap(fn($programming) => $programming->employees->pluck('id'))
            ->push($employee->id)
            ->unique()
            ->values();

        $shiftIds = $programaciones->pluck('shift_id')->unique();

        // 7. Consultar asistencias de hoy.
        $attendances = Attendance::query()
            ->whereDate('date', $today)
            ->whereIn('shift_id', $shiftIds)
            ->whereIn('employee_id', $employeeIds)
            ->where('type', 'Entrada')
            ->orderByDesc('id')
            ->get(['employee_id', 'shift_id', 'status'])
            ->groupBy(
                fn($attendance) =>
                $attendance->shift_id . ':' . $attendance->employee_id
            );

        // 8. Preparar nombres de los días.
        $dayNames = [
            1 => 'Lun',
            2 => 'Mar',
            3 => 'Mié',
            4 => 'Jue',
            5 => 'Vie',
            6 => 'Sáb',
            7 => 'Dom',
        ];

        // 9. Construir respuesta.
        $data = $programaciones->map(function ($programming) use (
            $employee,
            $attendances,
            $dayNames
        ) {
            $snapshot = $programming->snapshot ?? [];

            $team = $programming->employees
                ->unique('id')
                ->values();

            // Garantizar que el conductor esté incluido.
            if (!$team->contains(
                fn($person) =>
                (int) $person->id === (int) $programming->driver_id
            )) {
                $team->prepend($employee);
            }

            $helperNames = array_values($snapshot['helpers'] ?? []);
            $helperIndex = 0;

            // Información de cada integrante y asistencia.
            $people = $team->map(function ($person) use (
                $programming,
                $snapshot,
                $attendances,
                &$helperIndex
            ) {
                $isDriver = (int) $person->id ===
                    (int) $programming->driver_id;

                if ($isDriver) {
                    $name = $snapshot['driver']
                        ?? trim($person->first_name . ' ' . $person->last_name);

                    $role = 'driver';
                } else {
                    $name = $helperNames[$helperIndex]
                        ?? trim($person->first_name . ' ' . $person->last_name);

                    $helperIndex++;
                    $role = 'helper';
                }

                $key = $programming->shift_id . ':' . $person->id;

                $records = $attendances->get($key, collect());

                $validAttendance = $records->first(
                    fn($attendance) =>
                    in_array(
                        $attendance->status,
                        ['Presente', 'Tardanza'],
                        true
                    )
                );

                $lastAttendance = $records->first();

                return [
                    'id' => $person->id,
                    'name' => $name,
                    'role' => $role,

                    'attendance_status' => $validAttendance?->status
                        ?? ($lastAttendance?->status ?? 'Pendiente'),

                    'attended' => $validAttendance !== null,
                ];
            })->values();

            // Comprobar si todos registraron su entrada.
            $complete = $people->isNotEmpty()
                && $people->every(fn($person) => $person['attended']);

            $shift = $programming->shift;
            $now = CarbonImmutable::now('America/Lima');
            $startAt = null;
            $endAt = null;
            $snapshotHours = trim((string) ($snapshot['hours'] ?? ''));

            // Evaluar con el mismo horario asignado y mostrado en la programación.
            if (
                $snapshotHours !== '' &&
                preg_match('/(\\d{1,2}:\\d{2})\\s*[-–—]\\s*(\\d{1,2}:\\d{2})/', $snapshotHours, $matches)
            ) {
                $startTime = $matches[1];
                $endTime = $matches[2];
            } elseif ($shift && $shift->start_time && $shift->end_time) {
                $startTime = substr($shift->start_time, 0, 5);
                $endTime = substr($shift->end_time, 0, 5);
            } else {
                $startTime = null;
                $endTime = null;
            }

            if ($startTime !== null && $endTime !== null) {
                $programmingDate = $programming->date->toDateString();
                $startAt = CarbonImmutable::parse(
                    $programmingDate . ' ' . $startTime,
                    'America/Lima'
                );
                $endAt = CarbonImmutable::parse(
                    $programmingDate . ' ' . $endTime,
                    'America/Lima'
                );

                // Los turnos nocturnos terminan al día siguiente.
                if ($endAt->lessThanOrEqualTo($startAt)) {
                    $endAt = $endAt->addDay();
                }
            }

            $routeStarted = $programming->started_at !== null;
            $routeFinished = $programming->status === Programming::STATUS_FINISHED
                || $programming->finished_at !== null;
            $validStatus = in_array($programming->status, [
                Programming::STATUS_PROGRAMMED,
                Programming::STATUS_RESCHEDULED,
            ], true);

            // El inicio solo se habilita cuando todo el personal asignado
            // (incluido el conductor) tiene asistencia válida.
            $canStartRoute = $validStatus
                && !$routeStarted
                && $complete
                && $startAt !== null
                && $endAt !== null
                && $now->greaterThanOrEqualTo($startAt)
                && $now->lessThan($endAt);

            $canFinishRoute = $routeStarted && !$routeFinished;

            return [
                'id' => $programming->id,

                'date' => $programming->date->toDateString(),

                'day' => $dayNames[$programming->date->dayOfWeekIso],

                'zone' => $snapshot['zone'] ?? null,

                'vehicle' => $snapshot['vehicle']
                    ?? $programming->vehicle?->name,

                'plate' => $snapshot['plate']
                    ?? $programming->vehicle?->plate,

                'shift' => $snapshot['shift']
                    ?? $shift?->name,

                'hours' => $snapshot['hours']
                    ?? ($shift
                        ? substr($shift->start_time, 0, 5) . ' - ' .
                        substr($shift->end_time, 0, 5)
                        : null),

                'status' => $programming->status,

                'people' => $people,

                'present_count' => $people->where('attended', true)->count(),

                'missing_count' => $people->where('attended', false)->count(),

                'complete' => $complete,

                // La lista tampoco habilita el inicio mientras falte alguna asistencia.
                'can_start_route' => $canStartRoute,
                'route_started' => $routeStarted,
                'route_finished' => $routeFinished,
                'can_finish_route' => $canFinishRoute,
                'started_at' => $programming->started_at?->timezone('America/Lima')->format('H:i'),
                'finished_at' => $programming->finished_at?->timezone('America/Lima')->format('H:i'),
                'start_window' => $startAt && $endAt ? [
                    'start' => $startAt->format('H:i'),
                    'end' => $endAt->format('H:i'),
                ] : null,
                'server_time' => $now->format('H:i:s'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'has_programming' => true,
            'message' => 'Programaciones de hoy obtenidas correctamente.',
            'date' => $today,
            'total' => $data->count(),
            'data' => $data,
        ], 200);
    }
}
