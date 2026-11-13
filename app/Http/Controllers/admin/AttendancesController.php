<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\Shift;
use App\Services\AttendanceMarkingRules;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class AttendancesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = $request->validate([
                'date_from' => ['nullable', 'date_format:Y-m-d'],
                'date_to' => ['nullable', 'date_format:Y-m-d'],
                'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
                'status' => ['nullable', Rule::in(Attendance::STATUSES)],
                'origin' => ['nullable', Rule::in(Attendance::ORIGINS)],
            ]);

            $today = today()->toDateString();
            $dateFrom = $filters['date_from'] ?? $today;
            $dateTo = $filters['date_to'] ?? $today;
            if ($dateFrom > $dateTo) {
                throw ValidationException::withMessages([
                    'date_to' => 'La fecha final no puede ser anterior a la fecha inicial.',
                ]);
            }

            // Una jornada nocturna comienza en una fecha y puede terminar al día siguiente.
            // La expresión cambia según el motor para mantener compatibilidad con SQLite y MySQL.
            $driver = DB::connection()->getDriverName();
            $nextDayFromEntry = $driver === 'sqlite'
                ? "DATE(exits.date) = DATE(entries.date, '+1 day')"
                : 'DATE(exits.date) = DATE_ADD(DATE(entries.date), INTERVAL 1 DAY)';
            $nextDayFromOrphan = $driver === 'sqlite'
                ? "DATE(orphan.date) = DATE(entries.date, '+1 day')"
                : 'DATE(orphan.date) = DATE_ADD(DATE(entries.date), INTERVAL 1 DAY)';

            // La fila principal representa una entrada; su salida se muestra en la misma fila.
            $entryRows = DB::table('attendances as entries')
                ->join('employees', 'employees.id', '=', 'entries.employee_id')
                ->join('shifts', 'shifts.id', '=', 'entries.shift_id')
                ->leftJoin('attendances as exits', function ($join) use ($nextDayFromEntry) {
                    $join->on('exits.employee_id', '=', 'entries.employee_id')
                        ->on('exits.shift_id', '=', 'entries.shift_id')
                        ->where('exits.type', 'Salida')
                        ->where(function ($dates) use ($nextDayFromEntry) {
                            $dates->where(function ($sameDay) {
                                $sameDay->whereColumn('exits.date', 'entries.date')
                                    ->whereColumn('exits.time', '>', 'entries.time');
                            })->orWhere(function ($nextDay) use ($nextDayFromEntry) {
                                $nextDay->whereRaw($nextDayFromEntry)
                                    ->whereRaw('shifts.end_time <= shifts.start_time');
                            });
                        });
                })
                ->where('entries.type', 'Entrada')
                ->select([
                    'entries.id as id',
                    'entries.employee_id as employee_id',
                    'entries.shift_id as shift_id',
                    'entries.date as attendance_date',
                    'entries.time as entry_time',
                    'exits.time as exit_time',
                    'entries.status as status',
                    DB::raw("COALESCE(entries.origin, 'No programado') as origin"),
                    'entries.notes as notes',
                    'employees.dni as dni',
                    'employees.first_name as first_name',
                    'employees.last_name as last_name',
                    'shifts.name as shift_name',
                    'shifts.start_time as shift_start_time',
                    'shifts.end_time as shift_end_time',
                    DB::raw("'Entrada' as record_type"),
                ]);

            // No ocultar datos históricos: una salida antigua sin entrada asociada aparece como fila independiente.
            $orphanExitRows = DB::table('attendances as orphan')
                ->join('employees', 'employees.id', '=', 'orphan.employee_id')
                ->join('shifts', 'shifts.id', '=', 'orphan.shift_id')
                ->where('orphan.type', 'Salida')
                ->whereNotExists(function ($query) use ($nextDayFromOrphan) {
                    $query->selectRaw('1')
                        ->from('attendances as entries')
                        ->join('shifts as entry_shifts', 'entry_shifts.id', '=', 'entries.shift_id')
                        ->whereColumn('entries.employee_id', 'orphan.employee_id')
                        ->whereColumn('entries.shift_id', 'orphan.shift_id')
                        ->where('entries.type', 'Entrada')
                        ->where(function ($dates) use ($nextDayFromOrphan) {
                            $dates->where(function ($sameDay) {
                                $sameDay->whereColumn('entries.date', 'orphan.date')
                                    ->whereColumn('entries.time', '<', 'orphan.time');
                            })->orWhere(function ($nextDay) use ($nextDayFromOrphan) {
                                $nextDay->whereRaw($nextDayFromOrphan)
                                    ->whereRaw('entry_shifts.end_time <= entry_shifts.start_time');
                            });
                        });
                })
                ->select([
                    'orphan.id as id',
                    'orphan.employee_id as employee_id',
                    'orphan.shift_id as shift_id',
                    'orphan.date as attendance_date',
                    DB::raw('NULL as entry_time'),
                    'orphan.time as exit_time',
                    'orphan.status as status',
                    DB::raw("COALESCE(orphan.origin, 'No programado') as origin"),
                    'orphan.notes as notes',
                    'employees.dni as dni',
                    'employees.first_name as first_name',
                    'employees.last_name as last_name',
                    'shifts.name as shift_name',
                    'shifts.start_time as shift_start_time',
                    'shifts.end_time as shift_end_time',
                    DB::raw("'Salida' as record_type"),
                ]);

            $attendances = DB::query()->fromSub($entryRows->unionAll($orphanExitRows), 'attendance_rows')
                ->whereDate('attendance_rows.attendance_date', '>=', $dateFrom)
                ->whereDate('attendance_rows.attendance_date', '<=', $dateTo);

            if (! empty($filters['shift_id'])) {
                $attendances->where('attendance_rows.shift_id', $filters['shift_id']);
            }

            if (! empty($filters['status'])) {
                $attendances->where('attendance_rows.status', $filters['status']);
            }

            if (! empty($filters['origin'])) {
                $attendances->where('attendance_rows.origin', $filters['origin']);
            }

            return DataTables::queryBuilder($attendances)
                ->filterColumn('employee_name', function ($query, $keyword) {
                    $terms = preg_split('/\s+/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [$keyword];

                    $query->where(function ($nameQuery) use ($terms) {
                        foreach ($terms as $term) {
                            $nameQuery->where(function ($termQuery) use ($term) {
                                $termQuery->where('attendance_rows.first_name', 'like', '%'.$term.'%')
                                    ->orWhere('attendance_rows.last_name', 'like', '%'.$term.'%');
                            });
                        }
                    });
                })
                ->orderColumn('employee_name', function ($query, $order) {
                    $query->orderBy('attendance_rows.last_name', $order)
                        ->orderBy('attendance_rows.first_name', $order);
                })
                ->orderColumn('date_display', function ($query, $order) {
                    $query->orderBy('attendance_rows.attendance_date', $order)
                        ->orderByRaw('COALESCE(attendance_rows.entry_time, attendance_rows.exit_time) '.$order);
                })
                ->orderColumn('shift_display', function ($query, $order) {
                    $query->orderBy('attendance_rows.shift_name', $order);
                })
                ->addColumn('employee_name', fn ($row) => trim($row->first_name.' '.$row->last_name))
                ->addColumn('date_display', fn ($row) => CarbonImmutable::parse($row->attendance_date)->format('d/m/Y'))
                ->addColumn('shift_display', fn ($row) => $row->shift_name.' ('.substr($row->shift_start_time, 0, 5).' - '.substr($row->shift_end_time, 0, 5).')')
                ->addColumn('entry_display', fn ($row) => $row->entry_time ? substr($row->entry_time, 0, 5) : '<span class="text-muted">—</span>')
                ->addColumn('exit_display', fn ($row) => $row->exit_time
                    ? substr($row->exit_time, 0, 5)
                    : '<span class="badge bg-light text-secondary border">Pendiente</span>')
                ->addColumn('status_badge', function ($row) {
                    $class = match ($row->status) {
                        'Presente' => 'bg-success',
                        'Tardanza' => 'bg-warning text-dark',
                        default => 'bg-secondary',
                    };

                    return '<span class="badge '.$class.'">'.e($row->status).'</span>';
                })
                ->addColumn('origin_badge', function ($row) {
                    $class = match ($row->origin) {
                        'Programado' => 'bg-primary',
                        'Retén' => 'bg-info text-dark',
                        default => 'bg-secondary',
                    };

                    return '<span class="badge '.$class.'">'.e($row->origin ?: 'No programado').'</span>';
                })
                ->addColumn('actions', function ($row) {
                    return '<div class="attendance-actions">'
                        .'<button type="button" class="btn btn-outline-primary btn-sm btnVer" data-url="'.e(route('attendances.show', $row->id)).'" title="Ver" aria-label="Ver asistencia"><i class="bi bi-eye"></i></button> '
                        .'<button type="button" class="btn btn-outline-warning btn-sm btnEditar" data-url="'.e(route('attendances.edit', $row->id)).'" title="Editar" aria-label="Editar asistencia"><i class="bi bi-pencil-square"></i></button> '
                        .'<button type="button" class="btn btn-outline-danger btn-sm btnEliminar" data-url="'.e(route('attendances.destroy', $row->id)).'" title="Eliminar" aria-label="Eliminar asistencia"><i class="bi bi-trash"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['entry_display', 'exit_display', 'status_badge', 'origin_badge', 'actions'])
                ->toJson();
        }

        return view('admin.attendances.index', [
            'shifts' => Shift::query()->orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']),
        ]);
    }

    public function create()
    {
        return view('admin.attendances.create', $this->formData());
    }

    public function store(Request $request, AttendanceMarkingRules $rules)
    {
        $data = $this->validatedData($request);

        try {
            $attendance = DB::transaction(function () use ($data, $rules) {
                $this->lockEmployee($data['employee_id']);
                $shift = Shift::query()->findOrFail($data['shift_id']);
                $markedAt = CarbonImmutable::parse($data['date'].' '.$data['time']);

                if ($data['type'] === 'Entrada') {
                    $this->assertEligible($data['employee_id'], $data['date']);

                    if (Attendance::query()->where('employee_id', $data['employee_id'])
                        ->where('shift_id', $data['shift_id'])
                        ->whereDate('date', $data['date'])
                        ->where('type', 'Entrada')->exists()) {
                        throw ValidationException::withMessages([
                            'type' => 'El empleado ya tiene una entrada registrada en este turno y fecha.',
                        ]);
                    }

                    if ($rules->pendingExits($data['employee_id'], $markedAt)->isNotEmpty()) {
                        throw ValidationException::withMessages([
                            'type' => 'El empleado tiene una entrada pendiente de salida. Registre primero la salida correspondiente.',
                        ]);
                    }

                    if (Attendance::query()->where('employee_id', $data['employee_id'])
                        ->where('shift_id', $data['shift_id'])
                        ->whereDate('date', $data['date'])
                        ->where('type', 'Salida')->exists()) {
                        throw ValidationException::withMessages([
                            'type' => 'Ya existe una salida para ese turno y fecha. No se puede agregar otra entrada.',
                        ]);
                    }

                    $data['status'] = $rules->entryStatus($shift, $data['date'], $markedAt);
                    $data['origin'] = $this->attendanceOrigin($data['employee_id'], $data['shift_id'], $data['date']);
                } else {
                    $pending = $rules->pendingExits($data['employee_id'], $markedAt);
                    if ($pending->count() > 1) {
                        throw ValidationException::withMessages([
                            'type' => 'El empleado tiene varias entradas pendientes. Revise sus marcaciones antes de registrar una salida.',
                        ]);
                    }
                    $entry = $pending->first(fn ($row) => (int) $row->shift_id === (int) $data['shift_id']);

                    if (! $entry) {
                        throw ValidationException::withMessages([
                            'type' => 'No existe una entrada pendiente para este empleado y turno. No se puede registrar una salida aislada.',
                        ]);
                    }

                    if (Attendance::query()->where('employee_id', $data['employee_id'])
                        ->where('shift_id', $data['shift_id'])
                        ->whereDate('date', $data['date'])
                        ->where('type', 'Salida')->exists()) {
                        throw ValidationException::withMessages([
                            'type' => 'Ya existe una salida para ese turno y fecha.',
                        ]);
                    }

                    $data['status'] = 'Presente';
                    $data['origin'] = $entry->origin ?? 'No programado';
                }

                return Attendance::create($data);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'type' => 'La marcación ya existe. No se permiten entradas ni salidas duplicadas.',
                ]);
            }

            throw $exception;
        }

        return response()->json([
            'mensaje' => 'Asistencia registrada correctamente.',
            'data' => ['id' => $attendance->id, 'status' => $attendance->status, 'origin' => $attendance->origin],
        ], 201);
    }

    public function show(Attendance $attendance)
    {
        $employee = DB::table('employees')
            ->where('id', $attendance->employee_id)
            ->first(['id', 'dni', 'first_name', 'last_name']);

        $shift = DB::table('shifts')
            ->where('id', $attendance->shift_id)
            ->first(['id', 'name', 'start_time', 'end_time']);

        $exitAttendance = $this->matchingExitForEntry($attendance);

        return view('admin.attendances.show', compact('attendance', 'employee', 'shift', 'exitAttendance'));
    }

    public function edit(Attendance $attendance)
    {
        return view('admin.attendances.edit', $this->formData($attendance->date->format('Y-m-d')) + compact('attendance'));
    }

    public function update(Request $request, Attendance $attendance, AttendanceMarkingRules $rules)
    {
        $data = $this->validatedData($request);

        try {
            DB::transaction(function () use ($data, $attendance, $rules) {
                $this->lockEmployee($data['employee_id']);
                $locked = Attendance::query()->whereKey($attendance->id)->lockForUpdate()->firstOrFail();
                $shift = Shift::query()->findOrFail($data['shift_id']);
                $markedAt = CarbonImmutable::parse($data['date'].' '.$data['time']);

                $duplicate = Attendance::query()
                    ->where('employee_id', $data['employee_id'])
                    ->where('shift_id', $data['shift_id'])
                    ->whereDate('date', $data['date'])
                    ->where('type', $data['type'])
                    ->where('id', '<>', $locked->id)
                    ->exists();

                if ($duplicate) {
                    throw ValidationException::withMessages([
                        'type' => 'Ya existe una marcación del mismo tipo para ese trabajador, turno y fecha.',
                    ]);
                }

                if ($data['type'] === 'Entrada') {
                    $this->assertEligible($data['employee_id'], $data['date']);
                    $data['status'] = $rules->entryStatus($shift, $data['date'], $markedAt);
                    $data['origin'] = $this->attendanceOrigin($data['employee_id'], $data['shift_id'], $data['date']);
                } else {
                    $entry = Attendance::query()
                        ->where('employee_id', $data['employee_id'])
                        ->where('shift_id', $data['shift_id'])
                        ->where('type', 'Entrada')
                        ->where('id', '<>', $locked->id)
                        ->where(function ($query) use ($data, $shift) {
                            $query->whereDate('date', $data['date']);
                            if (substr((string) $shift->end_time, 0, 8) <= substr((string) $shift->start_time, 0, 8)) {
                                $query->orWhereDate('date', CarbonImmutable::parse($data['date'])->subDay()->toDateString());
                            }
                        })
                        ->get()
                        ->filter(function (Attendance $candidate) use ($markedAt) {
                            $candidateAt = CarbonImmutable::parse(
                                $candidate->date->toDateString().' '.$candidate->time,
                                $markedAt->getTimezone()
                            );

                            return $candidateAt->lessThan($markedAt);
                        })
                        ->sortByDesc(fn (Attendance $candidate) => CarbonImmutable::parse(
                            $candidate->date->toDateString().' '.$candidate->time,
                            $markedAt->getTimezone()
                        )->getTimestamp())
                        ->first();

                    if (! $entry) {
                        throw ValidationException::withMessages([
                            'type' => 'No se encontró una entrada anterior que corresponda a esta salida.',
                        ]);
                    }

                    $data['status'] = 'Presente';
                    $data['origin'] = $entry->origin ?? 'No programado';
                }

                $locked->update($data);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'type' => 'La marcación ya existe. No se permiten duplicados.',
                ]);
            }

            throw $exception;
        }

        return response()->json(['mensaje' => 'Asistencia actualizada correctamente.']);
    }

    private function lockEmployee(int $employeeId): void
    {
        DB::table('employees')->where('id', $employeeId)->lockForUpdate()->firstOrFail();
    }

    private function assertEligible(int $employeeId, string $date): void
    {
        $eligible = Employee::query()
            ->whereKey($employeeId)
            ->where('status', 'Activo')
            ->whereHas('contracts', function ($query) use ($date) {
                $query->where('is_active', true)
                    ->whereNull('cancelled_at')
                    ->whereDate('start_date', '<=', $date)
                    ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
            })
            ->whereDoesntHave('vacations', function ($query) use ($date) {
                $query->where('status', 'Aprobada')
                    ->whereDate('start_date', '<=', $date)
                    ->whereDate('end_date', '>=', $date);
            })
            ->exists();

        if (! $eligible) {
            throw ValidationException::withMessages([
                'employee_id' => 'El empleado debe estar activo, tener contrato vigente y no contar con vacaciones aprobadas en la fecha seleccionada.',
            ]);
        }
    }

    private function attendanceOrigin(int $employeeId, int $shiftId, string $date): string
    {
        $programmed = DB::table('programming_employees')
            ->join('programmings', 'programmings.id', '=', 'programming_employees.programming_id')
            ->where('programming_employees.employee_id', $employeeId)
            ->whereDate('programming_employees.date', $date)
            ->where('programming_employees.shift_id', $shiftId)
            ->whereIn('programmings.status', ['Programado', 'Finalizado'])
            ->whereNull('programmings.deleted_at')
            ->exists();

        if ($programmed) {
            return 'Programado';
        }

        $retainer = DB::table('employees')
            ->join('employee_types', 'employee_types.id', '=', 'employees.employee_type_id')
            ->where('employees.id', $employeeId)
            ->whereIn('employee_types.name', ['Conductor', 'Ayudante'])
            ->exists();

        return $retainer ? 'Retén' : 'No programado';
    }

    private function matchingExitForEntry(Attendance $entry): ?Attendance
    {
        if ($entry->type !== 'Entrada') {
            return null;
        }

        $shift = $entry->shift;
        $isOvernight = substr((string) $shift->end_time, 0, 8) <= substr((string) $shift->start_time, 0, 8);
        $entryAt = CarbonImmutable::parse($entry->date->toDateString().' '.$entry->time);

        $candidates = Attendance::query()
            ->where('employee_id', $entry->employee_id)
            ->where('shift_id', $entry->shift_id)
            ->where('type', 'Salida')
            ->where(function ($query) use ($entry, $isOvernight) {
                $query->whereDate('date', $entry->date->toDateString());

                if ($isOvernight) {
                    $query->orWhereDate('date', $entry->date->copy()->addDay()->toDateString());
                }
            })
            ->get();

        return $candidates
            ->filter(function (Attendance $candidate) use ($entryAt) {
                $exitAt = CarbonImmutable::parse($candidate->date->toDateString().' '.$candidate->time);

                return $exitAt->greaterThan($entryAt);
            })
            ->sortBy(fn (Attendance $candidate) => CarbonImmutable::parse(
                $candidate->date->toDateString().' '.$candidate->time
            )->getTimestamp())
            ->first();
    }

    public function destroy(Attendance $attendance)
    {
        DB::transaction(function () use ($attendance) {
            $locked = Attendance::query()->whereKey($attendance->id)->lockForUpdate()->firstOrFail();

            // Al eliminar una jornada desde su fila, eliminar también la salida emparejada.
            if ($locked->type === 'Entrada') {
                $this->matchingExitForEntry($locked)?->delete();
            }

            $locked->delete();
        });

        return response()->json(['mensaje' => 'Asistencia eliminada correctamente.']);
    }

    public function history(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $rows = Attendance::query()
            ->with('shift')
            ->where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])
            ->orderBy('time')
            ->get();

        return response()->json($rows->map(fn ($attendance) => [
            'time' => substr($attendance->time, 0, 5),
            'type' => $attendance->type,
            'status' => $attendance->status,
            'shift' => $attendance->shift?->name,
            'origin' => $attendance->origin ?? 'No programado',
        ]));
    }

    private function formData(?string $requestedDate = null): array
    {
        $date = $requestedDate ?? request()->input('date', today()->format('Y-m-d'));
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date)) {
            $date = today()->format('Y-m-d');
        }

        return [
            'employees' => DB::table('employees')
                ->select('employees.id', 'employees.dni', 'employees.first_name', 'employees.last_name')
                ->where('employees.status', 'Activo')
                ->whereExists(function ($query) use ($date) {
                    $query->selectRaw('1')->from('contracts')
                        ->whereColumn('contracts.employee_id', 'employees.id')
                        ->where('contracts.is_active', true)
                        ->whereNull('contracts.cancelled_at')
                        ->whereDate('contracts.start_date', '<=', $date)
                        ->where(fn ($dates) => $dates->whereNull('contracts.end_date')->orWhereDate('contracts.end_date', '>=', $date));
                })
                ->whereNotExists(function ($query) use ($date) {
                    $query->selectRaw('1')->from('vacations')
                        ->whereColumn('vacations.employee_id', 'employees.id')
                        ->where('vacations.status', 'Aprobada')
                        ->whereDate('vacations.start_date', '<=', $date)
                        ->whereDate('vacations.end_date', '>=', $date);
                })
                ->orderBy('employees.last_name')
                ->orderBy('employees.first_name')
                ->get(),
            'shifts' => Shift::query()
                ->select('id', 'name', 'start_time', 'end_time')
                ->orderBy('start_time')
                ->get(),
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::in(Attendance::TYPES)],
            'notes' => ['nullable', 'string'],
        ], [
            'employee_id.required' => 'Seleccione un empleado.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'shift_id.required' => 'Seleccione un turno.',
            'shift_id.exists' => 'El turno seleccionado no existe.',
            'date.required' => 'Ingrese la fecha de asistencia.',
            'date.date_format' => 'La fecha de asistencia no es válida.',
            'time.required' => 'Ingrese la hora de asistencia.',
            'time.date_format' => 'La hora de asistencia no es válida.',
            'type.required' => 'Seleccione el tipo de marcación.',
            'type.in' => 'El tipo de marcación no es válido.',
            'notes.string' => 'Las observaciones deben ser texto.',
        ]);
    }
}
