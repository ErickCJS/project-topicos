<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class AttendancesController extends Controller
{
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
                ->addColumn('employee_name', function ($attendance) {
                    return trim($attendance->first_name.' '.$attendance->last_name);
                })
                ->addColumn('date_time', function ($attendance) {
                    return $attendance->date->format('d/m/Y').' '.substr($attendance->time, 0, 5);
                })
                ->addColumn('actions', function ($attendance) {
                    return '<div class="attendance-actions">'
                        .'<button type="button" class="btn btn-outline-primary btn-sm attendance-action-btn btnVer" data-id="'.$attendance->id.'" data-url="'.e(route('attendances.show', $attendance->id)).'" title="Ver" aria-label="Ver asistencia"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-outline-warning btn-sm attendance-action-btn btnEditar" data-id="'.$attendance->id.'" data-url="'.e(route('attendances.edit', $attendance->id)).'" title="Editar" aria-label="Editar asistencia"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-outline-danger btn-sm attendance-action-btn btnEliminar" data-id="'.$attendance->id.'" data-url="'.e(route('attendances.destroy', $attendance->id)).'" title="Eliminar" aria-label="Eliminar asistencia"><i class="bi bi-trash" aria-hidden="true"></i></button>'
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
        return view('admin.attendances.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'dni' => ['required', 'digits:8'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $person =  Employee::where('dni', $data['dni'])->get();
        $attendance = Attendance::create();

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
        return view('admin.attendances.edit');
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
            'employee_id.exists' => 'El empleado seleccionado no existe.',
            'shift_id.required' => 'Seleccione un turno.',
            'shift_id.exists' => 'El turno seleccionado no existe.',
            'date.required' => 'Ingrese la fecha de asistencia.',
            'date.date_format' => 'La fecha de asistencia no es válida.',
            'time.required' => 'Ingrese la hora de asistencia.',
            'time.date_format' => 'La hora de asistencia no es válida.',
            'type.required' => 'Seleccione el tipo de marcación.',
            'type.in' => 'El tipo de marcación seleccionado no es válido.',
            'status.required' => 'Seleccione el estado de la asistencia.',
            'status.in' => 'El estado seleccionado no es válido.',
        ]);
    }

  
}
