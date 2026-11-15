<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\ProgrammingLocation;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverProgrammingController extends Controller
{
    private const TIMEZONE = 'America/Lima';
    private const LOCATION_DUPLICATE_RADIUS_METERS = 5.0;

    /**
     * Devuelve el detalle de una programación asignada al conductor autenticado.
     */
    public function show(Request $request, int $programmingId): JsonResponse
    {
        $employee = $request->user();

        if ($error = $this->validateDriverIdentity($employee)) {
            return $error;
        }

        $programming = Programming::query()
            ->with([
                'employees' => fn ($query) => $query->orderByPivot('id'),
                'vehicle',
                'shift',
            ])
            ->where('driver_id', $employee->id)
            ->whereKey($programmingId)
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->first();

        if (!$programming) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la programación solicitada.',
            ], 404);
        }

        // Si todavía no se ha iniciado, exige conductor activo y contrato vigente.
        // Si ya está abierta, permite consultar y finalizar aunque la vigencia cambie
        // durante el recorrido (por ejemplo, en un turno nocturno).
        if ($programming->started_at === null) {
            if ($employee->status !== 'Activo') {
                return response()->json([
                    'success' => false,
                    'message' => 'Tu cuenta de conductor no está activa.',
                ], 403);
            }

            if (!$employee->hasVigenteContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes un contrato vigente.',
                ], 403);
            }
        }

        $snapshot = $programming->snapshot ?? [];
        $date = $programming->date->toDateString();
        $shift = $programming->shift;

        $team = $programming->employees
            ->unique('id')
            ->values();

        // El conductor siempre aparece en el equipo.
        if (!$team->contains(fn ($person) => (int) $person->id === (int) $programming->driver_id)) {
            $team->prepend($employee);
        }

        $teamIds = $team->pluck('id')->unique()->values();

        $attendances = Attendance::query()
            ->whereDate('date', $date)
            ->where('shift_id', $programming->shift_id)
            ->whereIn('employee_id', $teamIds)
            ->where('type', 'Entrada')
            ->orderByDesc('id')
            ->get(['employee_id', 'status', 'time'])
            ->groupBy('employee_id');

        $dayNames = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
        ];

        $helperNames = array_values($snapshot['helpers'] ?? []);
        $helperIndex = 0;

        $people = $team->map(function ($person) use (
            $programming,
            $snapshot,
            $attendances,
            $helperNames,
            &$helperIndex
        ) {
            $isDriver = (int) $person->id === (int) $programming->driver_id;

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

            $records = $attendances->get($person->id, collect());
            $validAttendance = $records->first(
                fn ($attendance) => in_array($attendance->status, ['Presente', 'Tardanza'], true)
            );
            $lastAttendance = $records->first();

            return [
                'id' => $person->id,
                'name' => $name,
                'role' => $role,
                'attendance_status' => $validAttendance?->status
                    ?? ($lastAttendance?->status ?? 'Pendiente'),
                'attendance_time' => $validAttendance?->time
                    ?? ($lastAttendance?->time),
                'attended' => $validAttendance !== null,
            ];
        })->values();

        $complete = $people->isNotEmpty()
            && $people->every(fn ($person) => $person['attended']);

        $startAvailability = $this->startAvailability($programming);

        $data = [
            'id' => $programming->id,
            'date' => $date,
            'day' => $dayNames[$programming->date->dayOfWeekIso] ?? null,
            'zone' => $snapshot['zone'] ?? null,
            'vehicle' => $snapshot['vehicle'] ?? $programming->vehicle?->name,
            'plate' => $snapshot['plate'] ?? $programming->vehicle?->plate,
            'shift' => $snapshot['shift'] ?? $shift?->name,
            'hours' => $snapshot['hours'] ?? ($shift
                ? substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5)
                : null),
            'status' => $programming->status,
            'notes' => $programming->notes,
            'people' => $people,
            'present_count' => $people->where('attended', true)->count(),
            'missing_count' => $people->where('attended', false)->count(),
            'complete' => $complete,

            // La habilitación depende del horario y del estado del recorrido,
            // nunca de que todos los ayudantes hayan marcado asistencia.
            'can_start_route' => $startAvailability['can_start_route'],
            'route_started' => $startAvailability['route_started'],
            'route_finished' => $startAvailability['route_finished'],
            'can_finish_route' => $startAvailability['can_finish_route'],
            'started_at' => $startAvailability['started_at'],
            'finished_at' => $startAvailability['finished_at'],
            'start_window' => $startAvailability['start_window'],
            'start_message' => $startAvailability['message'],
            'finish_message' => $startAvailability['finish_message'],
            'server_time' => $startAvailability['server_time'],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Detalle de la programación obtenido correctamente.',
            'data' => $data,
        ]);
    }

    /**
     * Inicia el recorrido solo dentro del horario real del turno.
     * La validación se repite en el servidor aunque Flutter ya haya habilitado el botón.
     */
    public function start(Request $request, int $programmingId): JsonResponse
    {
        $employee = $request->user();

        if ($error = $this->validateDriver($employee)) {
            return $error;
        }

        return DB::transaction(function () use ($employee, $programmingId) {
            $programming = Programming::query()
                ->with('shift')
                ->where('driver_id', $employee->id)
                ->whereKey($programmingId)
                ->lockForUpdate()
                ->first();

            if (!$programming || $programming->status === Programming::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la programación solicitada.',
                ], 404);
            }

            if (!in_array($programming->status, [
                Programming::STATUS_PROGRAMMED,
                Programming::STATUS_RESCHEDULED,
            ], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este recorrido ya no se encuentra disponible para iniciar.',
                ], 409);
            }

            if ($programming->started_at !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este recorrido ya fue iniciado.',
                    'started_at' => $programming->started_at
                        ->timezone(self::TIMEZONE)
                        ->format('H:i'),
                ], 409);
            }

            $window = $this->scheduleWindow($programming);

            if ($window === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'El turno no tiene un horario válido configurado.',
                ], 422);
            }

            [$startAt, $endAt] = $window;
            $now = CarbonImmutable::now(self::TIMEZONE);

            // El inicio debe ocurrir dentro del turno: [hora inicial, hora final).
            // Una vez iniciado, la finalización puede ser después del horario.
            if ($now->lessThan($startAt) || !$now->lessThan($endAt)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Puedes iniciar el recorrido únicamente entre '
                        . $startAt->format('H:i') . ' y antes de las '
                        . $endAt->format('H:i') . '.',
                    'start_window' => [
                        'start' => $startAt->format('H:i'),
                        'end' => $endAt->format('H:i'),
                    ],
                ], 422);
            }

            $programming->forceFill([
                'started_by' => $employee->id,
                'started_at' => $now,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Recorrido iniciado correctamente.',
                'data' => [
                    'id' => $programming->id,
                    'route_started' => true,
                    'started_at' => $now->format('H:i'),
                ],
            ]);
        });
    }

    /**
     * Finaliza un recorrido que ya fue iniciado.
     * No existe límite de hora para finalizar: puede terminar antes o después
     * del horario normal del turno.
     */
    public function finish(Request $request, int $programmingId): JsonResponse
    {
        $employee = $request->user();

        if ($error = $this->validateDriverIdentity($employee)) {
            return $error;
        }

        return DB::transaction(function () use ($employee, $programmingId) {
            $programming = Programming::query()
                ->where('driver_id', $employee->id)
                ->whereKey($programmingId)
                ->lockForUpdate()
                ->first();

            if (!$programming || $programming->status === Programming::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la programación solicitada.',
                ], 404);
            }

            if ($programming->started_at === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes finalizar un recorrido que todavía no ha sido iniciado.',
                ], 409);
            }

            if (
                $programming->status === Programming::STATUS_FINISHED ||
                $programming->finished_at !== null
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este recorrido ya fue finalizado.',
                    'finished_at' => $programming->finished_at
                        ?->timezone(self::TIMEZONE)
                        ->format('H:i'),
                ], 409);
            }

            if (!in_array($programming->status, [
                Programming::STATUS_PROGRAMMED,
                Programming::STATUS_RESCHEDULED,
            ], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El estado del recorrido no permite finalizarlo.',
                ], 409);
            }

            $now = CarbonImmutable::now(self::TIMEZONE);

            if ($now->lessThan($programming->started_at->timezone(self::TIMEZONE))) {
                return response()->json([
                    'success' => false,
                    'message' => 'La hora de finalización no puede ser anterior al inicio.',
                ], 422);
            }

            $programming->forceFill([
                'status' => Programming::STATUS_FINISHED,
                'ended_by' => $employee->id,
                'finished_at' => $now,
            ])->save();

            return response()->json([
                'success' => true,
                'message' => 'Recorrido finalizado correctamente.',
                'data' => [
                    'id' => $programming->id,
                    'route_started' => true,
                    'route_finished' => true,
                    'started_at' => $programming->started_at
                        ->timezone(self::TIMEZONE)
                        ->format('H:i'),
                    'finished_at' => $now->format('H:i'),
                ],
            ]);
        });
    }


    /**
     * Recibe una posición GPS del conductor para un recorrido que ya inició.
     *
     * Flutter puede enviar un punto cada 5 segundos. Si el nuevo punto está
     * a 5 metros o menos del último punto guardado, se considera repetido
     * y no se inserta otra fila.
     */
    public function storeLocation(Request $request): JsonResponse
    {
        $employee = $request->user();

        if ($error = $this->validateDriverIdentity($employee)) {
            return $error;
        }

        if ($employee->status !== 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta de conductor no está activa.',
            ], 403);
        }

        $data = $request->validate([
            'id_programacion' => ['required', 'integer', 'min:1'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'between:0,1000'],
        ]);

        return DB::transaction(function () use ($employee, $data): JsonResponse {
            // El bloqueo serializa los envíos simultáneos para esta programación.
            // Así dos peticiones simultáneas no pueden guardar el mismo punto.
            $programming = Programming::query()
                ->whereKey((int) $data['id_programacion'])
                ->where('driver_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if (!$programming || $programming->status === Programming::STATUS_CANCELLED) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una programación asignada a tu cuenta.',
                ], 404);
            }

            if ($programming->started_at === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes iniciar el recorrido antes de enviar ubicaciones.',
                ], 409);
            }

            if (
                $programming->finished_at !== null ||
                $programming->status === Programming::STATUS_FINISHED
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'El recorrido ya fue finalizado. No se aceptan más ubicaciones.',
                ], 409);
            }

            $lastLocation = ProgrammingLocation::query()
                ->where('programming_id', $programming->id)
                ->orderByDesc('id')
                ->first();

            $latitude = (float) $data['latitud'];
            $longitude = (float) $data['longitud'];
            $distanceFromLast = null;

            if ($lastLocation) {
                $distanceFromLast = $this->distanceInMeters(
                    (float) $lastLocation->latitude,
                    (float) $lastLocation->longitude,
                    $latitude,
                    $longitude
                );

                if ($distanceFromLast <= self::LOCATION_DUPLICATE_RADIUS_METERS) {
                    return response()->json([
                        'success' => true,
                        'recorded' => false,
                        'duplicate' => true,
                        'message' => 'La ubicación no cambió significativamente. No se registró un punto duplicado.',
                        'data' => [
                            'id_programacion' => $programming->id,
                            'ultima_ubicacion_id' => $lastLocation->id,
                            'ultima_ubicacion_registrada' => $lastLocation->recorded_at
                                ->timezone(self::TIMEZONE)
                                ->toIso8601String(),
                            'distancia_desde_ultimo_metros' => round($distanceFromLast, 2),
                            'radio_duplicado_metros' => self::LOCATION_DUPLICATE_RADIUS_METERS,
                            'intervalo_envio_segundos' => 5,
                        ],
                    ], 200);
                }
            }

            $recordedAt = CarbonImmutable::now(self::TIMEZONE);

            $location = ProgrammingLocation::create([
                'programming_id' => $programming->id,
                'employee_id' => $employee->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_meters' => $data['precision_metros'] ?? null,
                'recorded_at' => $recordedAt,
            ]);

            return response()->json([
                'success' => true,
                'recorded' => true,
                'duplicate' => false,
                'message' => 'Ubicación registrada correctamente.',
                'data' => [
                    'id' => $location->id,
                    'id_programacion' => $programming->id,
                    'latitud' => (float) $location->latitude,
                    'longitud' => (float) $location->longitude,
                    'precision_metros' => $location->accuracy_meters !== null
                        ? (float) $location->accuracy_meters
                        : null,
                    'registrado_en' => $location->recorded_at
                        ->timezone(self::TIMEZONE)
                        ->toIso8601String(),
                    'distancia_desde_ultimo_metros' => $distanceFromLast !== null
                        ? round($distanceFromLast, 2)
                        : null,
                    'radio_duplicado_metros' => self::LOCATION_DUPLICATE_RADIUS_METERS,
                    'intervalo_envio_segundos' => 5,
                ],
            ], 201);
        }, 3);
    }

    /**
     * Distancia Haversine entre dos coordenadas GPS, en metros.
     */
    private function distanceInMeters(
        float $latitude1,
        float $longitude1,
        float $latitude2,
        float $longitude2
    ): float {
        $earthRadius = 6371000.0;

        $lat1 = deg2rad($latitude1);
        $lat2 = deg2rad($latitude2);
        $deltaLatitude = deg2rad($latitude2 - $latitude1);
        $deltaLongitude = deg2rad($longitude2 - $longitude1);

        $a = sin($deltaLatitude / 2) ** 2
            + cos($lat1) * cos($lat2) * sin($deltaLongitude / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(max(0.0, 1 - $a)));

        return $earthRadius * $c;
    }

    private function validateDriverIdentity($employee): ?JsonResponse
    {
        if (
            !$employee instanceof Employee ||
            (int) $employee->employee_type_id !== 1
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes autorización para realizar esta operación.',
            ], 403);
        }

        return null;
    }

    private function validateDriver($employee): ?JsonResponse
    {
        if ($error = $this->validateDriverIdentity($employee)) {
            return $error;
        }

        if ($employee->status !== 'Activo') {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta de conductor no está activa.',
            ], 403);
        }

        if (!$employee->hasVigenteContract()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un contrato vigente.',
            ], 403);
        }

        return null;
    }

    /**
     * Calcula el intervalo usando las horas configuradas en la tabla shifts.
     * También contempla turnos que terminan al día siguiente, por ejemplo 20:00 - 02:00.
     */
    private function scheduleWindow(Programming $programming): ?array
    {
        $shift = $programming->shift;
        $snapshot = $programming->snapshot ?? [];
        $snapshotHours = trim((string) ($snapshot['hours'] ?? ''));

        // La programación guarda una copia del horario que se asignó al conductor.
        // Usamos ese mismo horario que ve Flutter, aunque el turno maestro cambie después.
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
            return null;
        }

        $date = $programming->date->toDateString();
        $startAt = CarbonImmutable::parse($date . ' ' . $startTime, self::TIMEZONE);
        $endAt = CarbonImmutable::parse($date . ' ' . $endTime, self::TIMEZONE);

        // Los turnos nocturnos, como 20:00 - 02:00, terminan al día siguiente.
        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $endAt->addDay();
        }

        return [$startAt, $endAt];
    }

    private function startAvailability(Programming $programming): array
    {
        $now = CarbonImmutable::now(self::TIMEZONE);
        $window = $this->scheduleWindow($programming);
        $routeStarted = $programming->started_at !== null;
        $routeFinished = $programming->status === Programming::STATUS_FINISHED
            || $programming->finished_at !== null;
        $validStatus = in_array($programming->status, [
            Programming::STATUS_PROGRAMMED,
            Programming::STATUS_RESCHEDULED,
        ], true);

        $startWindow = null;
        $startedAt = $programming->started_at?->timezone(self::TIMEZONE)->format('H:i');
        $finishedAt = $programming->finished_at?->timezone(self::TIMEZONE)->format('H:i');
        $canStart = false;

        if ($window !== null) {
            [$startAt, $endAt] = $window;
            $startWindow = [
                'start' => $startAt->format('H:i'),
                'end' => $endAt->format('H:i'),
            ];
            $canStart = $validStatus
                && !$routeStarted
                && $now->greaterThanOrEqualTo($startAt)
                && $now->lessThan($endAt);
        }

        $canFinish = $routeStarted && !$routeFinished;

        if ($routeFinished) {
            $message = 'El recorrido se finalizó a las ' . $finishedAt . '.';
            $finishMessage = 'Este recorrido ya fue finalizado.';
        } elseif ($routeStarted) {
            $message = 'Recorrido iniciado a las ' . $startedAt . '. Puedes finalizarlo cuando termines el servicio.';
            $finishMessage = 'El recorrido sigue abierto. Finalízalo al terminar.';
        } elseif (!$validStatus) {
            $message = 'Este recorrido ya no se encuentra disponible para iniciar.';
            $finishMessage = 'No hay un recorrido abierto para finalizar.';
        } elseif ($window === null) {
            $message = 'El turno no tiene un horario válido configurado.';
            $finishMessage = 'No hay un recorrido abierto para finalizar.';
        } elseif ($now->lessThan($window[0])) {
            $message = 'Podrás iniciar el recorrido a las ' . $window[0]->format('H:i') . '.';
            $finishMessage = 'No hay un recorrido abierto para finalizar.';
        } elseif (!$now->lessThan($window[1])) {
            $message = 'El horario para iniciar finalizó a las ' . $window[1]->format('H:i') . '.';
            $finishMessage = 'No hay un recorrido abierto para finalizar.';
        } else {
            $message = 'Estás dentro del horario. Puedes iniciar el recorrido.';
            $finishMessage = 'No hay un recorrido abierto para finalizar.';
        }

        return [
            'can_start_route' => $canStart,
            'route_started' => $routeStarted,
            'route_finished' => $routeFinished,
            'can_finish_route' => $canFinish,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'start_window' => $startWindow,
            'message' => $message,
            'finish_message' => $finishMessage,
            'server_time' => $now->format('H:i:s'),
        ];
    }
}
