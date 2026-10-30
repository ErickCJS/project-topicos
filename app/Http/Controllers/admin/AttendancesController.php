<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class AttendancesController extends Controller
{
    public function worker()
    {
        return view('admin.attendances.worker', ['serverTime' => now()->toIso8601String()]);
    }

    public function workerStore(Request $request)
    {
        $credentials = $request->validate([
            'dni' => ['required', 'digits:8'],
            'password' => ['required', 'string', 'min:8'],
            'type' => ['required', Rule::in(Attendance::TYPES)],
        ]);
        $employee = $this->verifyWorker($credentials['dni'], $credentials['password']);

        $date = today()->toDateString();
        $time = now()->format('H:i:s');
        $contract = $employee->contracts()->where('is_active', true)->whereDate('start_date', '<=', $date)
            ->where(function ($query) use ($date) { $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date); })
            ->whereNull('cancelled_at')->exists();
        if (! $contract) {
            throw ValidationException::withMessages(['attendance' => 'No tiene un contrato vigente para hoy.']);
        }
        if ($employee->vacations()->where('status', 'Aprobada')->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->exists()) {
            throw ValidationException::withMessages(['attendance' => 'No puede registrar asistencia durante sus vacaciones.']);
        }

        $scheduledShifts = DB::table('programming_employees as pe')
            ->join('programmings as p', 'p.id', '=', 'pe.programming_id')
            ->join('shifts as s', 's.id', '=', 'pe.shift_id')
            ->where('pe.employee_id', $employee->id)->whereDate('pe.date', $date)->whereNull('p.deleted_at')
            ->select('s.id', 's.start_time', 's.end_time')->get();
        $matchingShifts = $scheduledShifts->filter(function ($scheduled) use ($time) {
            $clock = strtotime($time);
            $start = strtotime($scheduled->start_time);
            $end = strtotime($scheduled->end_time);
            return $start <= $end ? $clock >= $start && $clock <= $end : $clock >= $start || $clock <= $end;
        });
        if ($matchingShifts->count() !== 1) {
            throw ValidationException::withMessages(['attendance' => 'No tiene un turno programado válido para la hora actual.']);
        }
        $shift = $matchingShifts->first();

        $created = DB::transaction(function () use ($employee, $date, $time, $shift, $credentials) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $duplicate = Attendance::where('employee_id', $employee->id)->whereDate('date', $date)
                ->whereTime('time', $time)->where('type', $credentials['type'])->lockForUpdate()->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['type' => 'Esta marcación ya fue registrada.']);
            }
            return Attendance::create(['employee_id' => $employee->id, 'shift_id' => $shift->id, 'date' => $date, 'time' => $time, 'type' => $credentials['type'], 'status' => 'Presente']);
        });

        return response()->json(['mensaje' => 'Asistencia registrada correctamente.', 'registro' => ['date' => $created->date->format('d/m/Y'), 'time' => substr($created->time, 0, 5), 'type' => $created->type]]);
    }

    public function workerHistory(Request $request)
    {
        $credentials = $request->validate(['dni' => ['required', 'digits:8'], 'password' => ['required', 'string']]);
        $employee = $this->verifyWorker($credentials['dni'], $credentials['password']);
        $records = Attendance::with('shift')->where('employee_id', $employee->id)->orderByDesc('date')->orderByDesc('time')->limit(100)->get();
        return response()->json(['registros' => $records->map(fn ($attendance) => ['date' => $attendance->date->format('d/m/Y'), 'time' => substr($attendance->time, 0, 5), 'type' => $attendance->type, 'shift' => $attendance->shift?->name, 'status' => $attendance->status])]);
    }

    private function verifyWorker(string $dni, string $password): Employee
    {
        $employee = Employee::where('dni', $dni)->where('status', 'Activo')->first();
        if (! $employee || ! Hash::check($password, $employee->password)) {
            throw ValidationException::withMessages(['credentials' => 'El DNI o la contraseña no son correctos.']);
        }
        return $employee;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $attendances = Attendance::query()
                ->join('employees', 'attendances.employee_id', '=', 'employees.id')
                ->join('shifts', 'attendances.shift_id', '=', 'shifts.id')
                ->select([
                    'attendances.id',
                    'attendances.date',
                    'attendances.time',
                    'attendances.type',
                    'attendances.status',
                    'attendances.notes',
                    'employees.dni',
                    'employees.first_name',
                    'employees.last_name',
                    'shifts.name as shift_name',
                ]);

            return DataTables::eloquent($attendances)
                ->filterColumn('employee_name', function ($query, $keyword) {
                    $query->where(function ($nameQuery) use ($keyword) {
                        $nameQuery->where('employees.first_name', 'like', '%'.$keyword.'%')
                            ->orWhere('employees.last_name', 'like', '%'.$keyword.'%');
                    });
                })
                ->addColumn('employee_name', function ($attendance) {
                    return trim($attendance->first_name.' '.$attendance->last_name);
                })
                ->addColumn('date_time', function ($attendance) {
                    return $attendance->date->format('d/m/Y').' '.substr($attendance->time, 0, 5);
                })
                ->addColumn('actions', function ($attendance) {
                    return '<div class="attendance-actions">'
                        .'<button type="button" class="btn btn-info btn-sm attendance-action-btn btnVer" data-id="'.$attendance->id.'" data-url="'.e(route('attendances.show', $attendance->id)).'" title="Ver" aria-label="Ver asistencia"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-warning btn-sm attendance-action-btn btnEditar" data-id="'.$attendance->id.'" data-url="'.e(route('attendances.edit', $attendance->id)).'" title="Editar" aria-label="Editar asistencia"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-danger btn-sm attendance-action-btn btnEliminar" data-id="'.$attendance->id.'" data-url="'.e(route('attendances.destroy', $attendance->id)).'" title="Eliminar" aria-label="Eliminar asistencia"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.attendances.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.attendances.create', $this->formData());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $attendance = Attendance::create($this->validatedData($request));

        return response()->json([
            'mensaje' => 'Asistencia registrada correctamente.',
            'data' => ['id' => $attendance->id],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Attendance $attendance)
    {
        $employee = DB::table('employees')
            ->where('id', $attendance->employee_id)
            ->first(['id', 'dni', 'first_name', 'last_name']);
        $shift = DB::table('shifts')
            ->where('id', $attendance->shift_id)
            ->first(['id', 'name', 'start_time', 'end_time']);

        return view('admin.attendances.show', compact('attendance', 'employee', 'shift'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Attendance $attendance)
    {
        return view('admin.attendances.edit', $this->formData() + compact('attendance'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $attendance->update($this->validatedData($request));

        return response()->json(['mensaje' => 'Asistencia actualizada correctamente.']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json(['mensaje' => 'Asistencia eliminada correctamente.']);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'type' => ['required', Rule::in(Attendance::TYPES)],
            'status' => ['required', Rule::in(Attendance::STATUSES)],
            'notes' => ['nullable', 'string'],
        ], [
            'employee_id.required' => 'Seleccione un empleado.',
            'employee_id.integer' => 'El empleado seleccionado no es válido.',
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'shift_id.required' => 'Seleccione un turno.',
            'shift_id.integer' => 'El turno seleccionado no es válido.',
            'shift_id.exists' => 'El turno seleccionado no existe.',
            'date.required' => 'Ingrese la fecha de asistencia.',
            'date.date_format' => 'La fecha de asistencia no es válida.',
            'time.required' => 'Ingrese la hora de asistencia.',
            'time.date_format' => 'La hora de asistencia no es válida.',
            'type.required' => 'Seleccione el tipo de marcación.',
            'type.in' => 'El tipo de marcación seleccionado no es válido.',
            'status.required' => 'Seleccione el estado de la asistencia.',
            'status.in' => 'El estado seleccionado no es válido.',
            'notes.string' => 'Las observaciones deben ser texto.',
        ]);
    }

    public function history(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $rows = Attendance::with('shift')->where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])->orderBy('time')->get();
        return response()->json($rows->map(fn ($attendance) => [
            'time' => substr($attendance->time, 0, 5),
            'type' => $attendance->type,
            'status' => $attendance->status,
            'shift' => $attendance->shift?->name,
        ]));
    }

    private function formData(): array
    {
        return [
            'employees' => Employee::orderBy('last_name')->orderBy('first_name')->get(['id', 'dni', 'first_name', 'last_name']),
            'shifts' => Shift::orderBy('start_time')->get(['id', 'name', 'start_time', 'end_time']),
        ];
    }

  
}
