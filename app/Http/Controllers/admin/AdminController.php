<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    function index(Request $request)
    {
        $filters = $request->validate([
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'turno' => ['nullable', 'integer', 'exists:shifts,id'],
        ]);
        $fechaSeleccionada = $filters['fecha'] ?? today()->toDateString();
        $turnoSeleccionado = $filters['turno'] ?? '';
        $programaciones = Programming::query()
            ->with(['shift', 'employees' => fn ($query) => $query->orderByPivot('id')])
            ->whereDate('date', $fechaSeleccionada)
            ->when($turnoSeleccionado, fn ($query, $shiftId) => $query->where('shift_id', $shiftId))
            ->orderBy('id')
            ->get();
        $employeeIds = $programaciones->flatMap(fn ($programacion) => $programacion->employees->pluck('id'))->unique()->values();
        $presentes = Attendance::query()
            ->whereDate('date', $fechaSeleccionada)
            ->whereIn('shift_id', $programaciones->pluck('shift_id')->unique())
            ->whereIn('employee_id', $employeeIds)
            ->where('status', 'Presente')
            ->get(['employee_id', 'shift_id'])
            ->groupBy(fn ($attendance) => $attendance->shift_id.':'.$attendance->employee_id);
        $programaciones = $programaciones->map(function ($programacion) use ($presentes) {
            $snapshot = $programacion->snapshot ?? [];
            $helpers = array_values($snapshot['helpers'] ?? []);
            $helperIndex = 0;
            $personas = $programacion->employees->unique('id')->values()->map(function ($employee) use ($programacion, $snapshot, $helpers, $presentes, &$helperIndex) {
                $name = $employee->pivot->role === 'driver'
                    ? ($snapshot['driver'] ?? trim($employee->first_name.' '.$employee->last_name))
                    : ($helpers[0] ?? trim($employee->first_name.' '.$employee->last_name));
                if ($employee->pivot->role !== 'driver' && $helpers) {
                    $name = $helpers[$helperIndex] ?? $name;
                    $helperIndex++;
                }
                return [
                    'id' => $employee->id,
                    'name' => $name,
                    'role' => $employee->pivot->role,
                    'present' => $presentes->has($programacion->shift_id.':'.$employee->id),
                ];
            })->values();
            $missing = $personas->where('present', false)->values();
            return [
                'id' => $programacion->id,
                'zone' => $snapshot['zone'] ?? 'Zona sin asignar',
                'group' => $snapshot['group'] ?? 'Sin grupo',
                'shift' => $snapshot['shift'] ?? $programacion->shift?->name ?? 'Sin turno',
                'hours' => $snapshot['hours'] ?? ($programacion->shift ? substr($programacion->shift->start_time, 0, 5).' - '.substr($programacion->shift->end_time, 0, 5) : ''),
                'vehicle' => trim(($snapshot['vehicle'] ?? 'Sin vehículo').' '.($snapshot['plate'] ?? '')),
                'status' => $programacion->status,
                'people' => $personas,
                'present' => $personas->where('present', true)->values(),
                'missing' => $missing,
                'present_count' => $personas->where('present', true)->count(),
                'missing_count' => $missing->count(),
                'complete' => $missing->isEmpty(),
            ];
        });
        $totalProgramaciones = $programaciones->count();
        $totalCompletas = $programaciones->where('complete', true)->count();
        $totalIncompletas = $totalProgramaciones - $totalCompletas;
        $totalFaltantes = $programaciones->sum(fn ($programacion) => $programacion['missing']->count());
        $turnos = Shift::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.dashboard', compact(
            'programaciones', 'totalProgramaciones', 'totalCompletas', 'totalIncompletas',
            'totalFaltantes', 'fechaSeleccionada', 'turnoSeleccionado', 'turnos'
        ));
    }
}
