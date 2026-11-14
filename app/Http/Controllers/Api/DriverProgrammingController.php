<?php

namespace App\\Http\\Controllers\\Api;

use App\\Http\\Controllers\\Controller;
use App\\Models\\admin\\Attendance;
use App\\Models\\admin\\Employee;
use App\\Models\\admin\\Programming;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class DriverProgrammingController extends Controller
{
    /**
     * Devuelve el detalle de una programación asignada al conductor autenticado.
     */
    public function show(Request $request, int $programmingId): JsonResponse
    {
        $employee = $request->user();

        if (
            !$employee instanceof Employee ||
            (int) $employee->employee_type_id !== 1 ||
            $employee->status !== 'Activo'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes autorización para consultar esta programación.',
            ], 403);
        }

        if (!$employee->hasVigenteContract()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes un contrato vigente.',
            ], 403);
        }

        // La consulta queda limitada al conductor autenticado.
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

        $snapshot = $programming->snapshot ?? [];
        $date = $programming->date->toDateString();
        $shift = $programming->shift;

        $team = $programming->employees
            ->unique('id')
            ->values();

        // El conductor siempre aparece en el equipo, aunque no esté en la tabla pivote.
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
                    ?? $lastAttendance?->time,
                'attended' => $validAttendance !== null,
            ];
        })->values();

        $complete = $people->isNotEmpty()
            && $people->every(fn ($person) => $person['attended']);

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
            'can_start_route' => $complete && in_array(
                $programming->status,
                [Programming::STATUS_PROGRAMMED, Programming::STATUS_RESCHEDULED],
                true
            ),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Detalle de la programación obtenido correctamente.',
            'data' => $data,
        ]);
    }
}
