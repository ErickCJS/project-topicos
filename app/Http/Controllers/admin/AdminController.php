<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class AdminController extends Controller
{
    public function index()
    {
        $modulePresentation = [
            'vehiculos-colores' => ['icon' => 'fas fa-palette', 'description' => 'Configura los colores de identificación de la flota.'],
            'vehiculos-marcas' => ['icon' => 'fas fa-tags', 'description' => 'Consulta y administra las marcas de los vehículos.'],
            'vehiculos-modelos' => ['icon' => 'fas fa-cubes', 'description' => 'Organiza los modelos registrados en el sistema.'],
            'vehiculos-tipos' => ['icon' => 'fas fa-list', 'description' => 'Mantén los tipos de vehículos municipales.'],
            'vehiculos' => ['icon' => 'fas fa-truck', 'description' => 'Consulta y administra la flota municipal.'],
            'personal-tipos' => ['icon' => 'fas fa-id-badge', 'description' => 'Administra los tipos de personal del sistema.'],
            'personal' => ['icon' => 'fas fa-users', 'description' => 'Consulta y administra el personal municipal.'],
            'contratos' => ['icon' => 'fas fa-file-contract', 'description' => 'Revisa los contratos y su vigencia.'],
            'asistencias' => ['icon' => 'fas fa-user-check', 'description' => 'Registra y consulta la asistencia del personal.'],
            'vacaciones' => ['icon' => 'fas fa-umbrella-beach', 'description' => 'Consulta y gestiona las solicitudes de vacaciones.'],
            'turnos' => ['icon' => 'fas fa-clock', 'description' => 'Configura y consulta los turnos operativos.'],
            'zonas' => ['icon' => 'fas fa-map-marked-alt', 'description' => 'Consulta y administra las zonas de trabajo.'],
            'feriados' => ['icon' => 'fas fa-calendar-alt', 'description' => 'Administra el calendario de feriados.'],
            'grupos-personal' => ['icon' => 'fas fa-users-cog', 'description' => 'Organiza los grupos de trabajo.'],
            'programacion' => ['icon' => 'fas fa-route', 'description' => 'Organiza turnos, zonas y recorridos.'],
            'dashboard' => ['icon' => 'fas fa-chart-line', 'description' => 'Revisa el estado diario de la operación.'],
            'motivos' => ['icon' => 'fas fa-clipboard-list', 'description' => 'Administra los motivos de cambio de programación.'],
            'cambios' => ['icon' => 'fas fa-exchange-alt', 'description' => 'Consulta los cambios de programación.'],
            'cambios-masivos' => ['icon' => 'fas fa-layer-group', 'description' => 'Valida y aplica cambios de programación en bloque.'],
            'usuarios' => ['icon' => 'fas fa-user-cog', 'description' => 'Consulta y administra los usuarios del sistema.'],
        ];

        $quickModules = [];

        // Build the home shortcuts from the permission catalog, not from a
        // fixed list. A user who only has access to one submodule sees only
        // that submodule and is sent directly to its authorized listing.
        foreach (config('permissions.catalog', []) as $group => $catalogModules) {
            foreach ($catalogModules as $slug => [$title, $routePrefix, $actions]) {
                if (!in_array('view', $actions, true)) {
                    continue;
                }

                $routeName = $slug === 'dashboard' ? $routePrefix : $routePrefix.'.index';
                $permission = $slug.'.view';

                if (!Route::has($routeName) || !Gate::allows('permission', $permission)) {
                    continue;
                }

                $presentation = $modulePresentation[$slug] ?? [];

                $quickModules[] = [
                    'group' => $group,
                    'title' => $title,
                    'description' => $presentation['description'] ?? 'Consulta y gestiona esta sección del sistema.',
                    'icon' => $presentation['icon'] ?? 'fas fa-folder-open',
                    'route' => $routeName,
                    'permission' => $permission,
                ];
            }
        }

        return view('admin.index', compact('quickModules'));
    }

    public function dashboard(Request $request)
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
            ->where('type', 'Entrada')
            ->whereIn('status', ['Presente', 'Tardanza'])
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
                'date' => $programacion->date->toDateString(),
                'parent_programming_id' => $programacion->parent_programming_id,
                'child_programming_id' => $programacion->childProgrammings()->value('id'),
                'shift_id' => $programacion->shift_id,
                'vehicle_id' => $programacion->vehicle_id,
                'driver_id' => $programacion->driver_id,
                'helper_ids' => $programacion->employees->where('pivot.role', 'helper')->pluck('id')->values()->all(),
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
        $retenes = Attendance::query()
            ->with(['employee', 'shift'])
            ->whereDate('date', $fechaSeleccionada)
            ->where('type', 'Entrada')
            ->whereIn('status', ['Presente', 'Tardanza'])
            ->where('origin', 'Retén')
            ->when($turnoSeleccionado, fn ($query, $shiftId) => $query->where('shift_id', $shiftId))
            ->whereNotExists(function ($query) use ($fechaSeleccionada) {
                $query->selectRaw('1')->from('attendances as exits')
                    ->whereColumn('exits.employee_id', 'attendances.employee_id')
                    ->whereColumn('exits.shift_id', 'attendances.shift_id')
                    ->whereDate('exits.date', $fechaSeleccionada)
                    ->where('exits.type', 'Salida');
            })
            ->get()
            ->map(function (Attendance $attendance) use ($fechaSeleccionada) {
                $assigned = DB::table('programming_employees')
                    ->join('programmings', 'programmings.id', '=', 'programming_employees.programming_id')
                    ->where('programming_employees.employee_id', $attendance->employee_id)
                    ->whereDate('programming_employees.date', $fechaSeleccionada)
                    ->where('programming_employees.shift_id', $attendance->shift_id)
                    ->whereIn('programmings.status', [Programming::STATUS_PROGRAMMED, Programming::STATUS_RESCHEDULED])
                    ->whereNull('programmings.deleted_at')
                    ->exists();

                return [
                    'id' => $attendance->employee_id,
                    'name' => trim($attendance->employee->first_name.' '.$attendance->employee->last_name),
                    'dni' => $attendance->employee->dni,
                    'shift' => $attendance->shift->name,
                    'shift_id' => $attendance->shift_id,
                    'time' => substr($attendance->time, 0, 5),
                    'available' => ! $assigned,
                ];
            })->values();
        $totalProgramaciones = $programaciones->count();
        $totalCompletas = $programaciones->where('complete', true)->count();
        $totalIncompletas = $totalProgramaciones - $totalCompletas;
        $totalFaltantes = $programaciones->sum(fn ($programacion) => $programacion['missing']->count());
        $turnos = Shift::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.dashboard', compact(
            'programaciones', 'totalProgramaciones', 'totalCompletas', 'totalIncompletas',
            'totalFaltantes', 'fechaSeleccionada', 'turnoSeleccionado', 'turnos', 'retenes'
        ));
    }
}
