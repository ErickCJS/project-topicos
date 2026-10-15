<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AttendancesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        if ($request->ajax()) {
            $attendances = DB::table('attendances')
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
                ])
                ->get();

            return DataTables::of($attendances)
                ->addColumn('employee_name', function ($attendance) {
                    return trim($attendance->first_name . ' ' . $attendance->last_name);
                })
                ->addColumn('date_time', function ($attendance) {
                    return \Carbon\Carbon::parse($attendance->date . ' ' . $attendance->time)->format('d/m/Y H:i');
                })
                ->addColumn('actions', function ($attendance) {
                    return '<div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm btnEditar" data-id="' . $attendance->id . '" title="Editar" aria-label="Editar asistencia">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btnEliminar" data-id="' . $attendance->id . '" title="Eliminar" aria-label="Eliminar asistencia">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.attendances.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
