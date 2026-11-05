<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AttendancesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $attendances = Attendance::query()
                ->join('employees', 'attendances.employee_id', '=', 'employees.id')
                ->join('shifts', 'attendances.shift_id', '=', 'shifts.id')
                ->select([
                    'attendances.*',
                    'employees.dni',
                    'employees.first_name',
                    'employees.last_name',
                    'shifts.name as shift_name',
                ]);

            return DataTables::eloquent($attendances)
                ->filterColumn('employee_name', function ($query, $keyword) {
    $terms = preg_split('/\s+/', trim($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [$keyword];

    $query->where(function ($nameQuery) use ($terms) {
        foreach ($terms as $term) {
            $nameQuery->where(function ($termQuery) use ($term) {
                $termQuery->where('employees.first_name', 'like', '%'.$term.'%')
                    ->orWhere('employees.last_name', 'like', '%'.$term.'%');
            });
        }
    });
})
->orderColumn('employee_name', function ($query, $order) {
    $query->orderBy('employees.last_name', $order)
        ->orderBy('employees.first_name', $order);
})
                ->addColumn('employee_name', fn ($row) =>
                    trim($row->first_name.' '.$row->last_name)
                )
                ->addColumn('date_time', fn ($row) =>
                    $row->date->format('d/m/Y').' '.substr($row->time, 0, 5)
                )
                ->addColumn('actions', function ($row) {
                    return '<div class="attendance-actions">'
                        .'<button type="button" class="btn btn-outline-primary btn-sm btnVer" data-url="'.e(route('attendances.show', $row->id)).'" title="Ver" aria-label="Ver asistencia"><i class="bi bi-eye"></i></button> '
                        .'<button type="button" class="btn btn-outline-warning btn-sm btnEditar" data-url="'.e(route('attendances.edit', $row->id)).'" title="Editar" aria-label="Editar asistencia"><i class="bi bi-pencil-square"></i></button> '
                        .'<button type="button" class="btn btn-outline-danger btn-sm btnEliminar" data-url="'.e(route('attendances.destroy', $row->id)).'" title="Eliminar" aria-label="Eliminar asistencia"><i class="bi bi-trash"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.attendances.index');
    }

    public function create()
    {
        return view('admin.attendances.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $eligible = DB::table('employees')->where('employees.id', $data['employee_id'])
            ->whereExists(function ($query) use ($data) {
                $query->selectRaw('1')->from('contracts')
                    ->whereColumn('contracts.employee_id', 'employees.id')
                    ->where('contracts.is_active', true)->whereNull('contracts.cancelled_at')
                    ->whereDate('contracts.start_date', '<=', $data['date'])
                    ->where(function ($endDate) use ($data) {
                        $endDate->whereNull('contracts.end_date')->orWhereDate('contracts.end_date', '>=', $data['date']);
                    });
            })
            ->whereNotExists(function ($query) use ($data) {
                $query->selectRaw('1')->from('vacations')
                    ->whereColumn('vacations.employee_id', 'employees.id')
                    ->where('vacations.status', 'Aprobada')
                    ->whereDate('vacations.start_date', '<=', $data['date'])
                    ->whereDate('vacations.end_date', '>=', $data['date']);
            })->exists();
        if (! $eligible) {
            throw \Illuminate\Validation\ValidationException::withMessages(['employee_id' => 'El empleado no tiene un contrato vigente para la fecha de asistencia o tiene vacaciones aprobadas.']);
        }


        $attendance = Attendance::create($data);

        return response()->json([
            'mensaje' => 'Asistencia registrada correctamente.',
            'data' => ['id' => $attendance->id],
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

        return view('admin.attendances.show', compact(
            'attendance', 'employee', 'shift'
        ));
    }

    public function edit(Attendance $attendance)
    {
        return view(
            'admin.attendances.edit',
            $this->formData() + compact('attendance')
        );
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $this->validatedData($request);
        $eligible = DB::table('employees')->where('employees.id', $data['employee_id'])
            ->whereExists(function ($query) use ($data) {
                $query->selectRaw('1')->from('contracts')
                    ->whereColumn('contracts.employee_id', 'employees.id')
                    ->where('contracts.is_active', true)->whereNull('contracts.cancelled_at')
                    ->whereDate('contracts.start_date', '<=', $data['date'])
                    ->where(function ($endDate) use ($data) {
                        $endDate->whereNull('contracts.end_date')->orWhereDate('contracts.end_date', '>=', $data['date']);
                    });
            })
            ->whereNotExists(function ($query) use ($data) {
                $query->selectRaw('1')->from('vacations')
                    ->whereColumn('vacations.employee_id', 'employees.id')
                    ->where('vacations.status', 'Aprobada')
                    ->whereDate('vacations.start_date', '<=', $data['date'])
                    ->whereDate('vacations.end_date', '>=', $data['date']);
            })->exists();
        if (! $eligible) {
            throw \Illuminate\Validation\ValidationException::withMessages(['employee_id' => 'El empleado no tiene un contrato vigente para la fecha de asistencia o tiene vacaciones aprobadas.']);
        }

        $attendance->update($data);

        return response()->json([
            'mensaje' => 'Asistencia actualizada correctamente.',
        ]);
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return response()->json([
            'mensaje' => 'Asistencia eliminada correctamente.',
        ]);
    }

    public function history(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $rows = Attendance::with('shift')
            ->where('employee_id', $data['employee_id'])
            ->whereDate('date', $data['date'])
            ->orderBy('time')
            ->get();

        return response()->json($rows->map(fn ($attendance) => [
            'time' => substr($attendance->time, 0, 5),
            'type' => $attendance->type,
            'status' => $attendance->status,
            'shift' => $attendance->shift?->name,
        ]));
    }

    private function formData(): array
    {
        $date = request()->input('date', today()->format('Y-m-d'));
        if (! is_string($date) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! strtotime($date)) {
            $date = today()->format('Y-m-d');
        }

        return [
            'employees' => DB::table('employees')
                ->select('employees.id', 'employees.dni', 'employees.first_name', 'employees.last_name')
                ->whereExists(function ($query) use ($date) {
                    $query->selectRaw('1')->from('contracts')
                        ->whereColumn('contracts.employee_id', 'employees.id')
                        ->where('contracts.is_active', true)->whereNull('contracts.cancelled_at')
                        ->whereDate('contracts.start_date', '<=', $date)
                        ->where(function ($endDate) use ($date) {
                            $endDate->whereNull('contracts.end_date')->orWhereDate('contracts.end_date', '>=', $date);
                        });
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

            'shifts' => DB::table('shifts')
                ->select('id', 'name', 'start_time', 'end_time')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'employee_id' => [
                'required', 'integer', 'exists:employees,id',
            ],
            'shift_id' => [
                'required', 'integer', 'exists:shifts,id',
            ],
            'date' => [
                'required', 'date_format:Y-m-d',
            ],
            'time' => [
                'required', 'date_format:H:i',
            ],
            'type' => [
                'required', Rule::in(Attendance::TYPES),
            ],
            'status' => [
                'required', Rule::in(Attendance::STATUSES),
            ],
            'notes' => [
                'nullable', 'string',
            ],
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
            'status.required' => 'Seleccione el estado.',
            'status.in' => 'El estado seleccionado no es válido.',
            'notes.string' => 'Las observaciones deben ser texto.',
        ]);
    }
}
