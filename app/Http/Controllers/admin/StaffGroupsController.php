<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StaffGroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $query = StaffGroup::query()
                ->join('shifts', 'staff_groups.shift_id', '=', 'shifts.id')
                ->join('zones', 'staff_groups.zone_id', '=', 'zones.id')
                ->join('vehicles', 'staff_groups.vehicle_id', '=', 'vehicles.id')
                ->with(['days', 'helpers', 'driver'])
                ->select([
                    'staff_groups.*',
                    'shifts.name as shift_name',
                    'zones.name as zone_name',
                    'vehicles.name as vehicle_name',
                ]);


            return DataTables::eloquent($query)
                ->addColumn('days', function ($staffgroup) {
                    $daysMap = [
                        1 => 'Lun',
                        2 => 'Mar',
                        3 => 'Mié',
                        4 => 'Jue',
                        5 => 'Vie',
                        6 => 'Sáb',
                        7 => 'Dom',
                    ];
                    $badges = $staffgroup->days
                        ->sortBy('day')
                        ->map(function ($item) use ($daysMap) {
                            $day = $daysMap[$item->day] ?? '?';

                            return '<span class="badge rounded-pill bg-primary">'
                                . $day .
                                '</span>';
                        })
                        ->implode('');

                    return '<div class="d-flex flex-wrap gap-2 align-items-center">'
                        . $badges .
                        '</div>'; 
                })
                ->addColumn('state', function ($staffgroup) {
                    if($staffgroup->state == 1){
                        return '<span class="badge rounded-pill bg-success">'
                            . 'Activo' .
                            '</span>';
                    }else{
                        return '<span class="badge rounded-pill bg-danger">'
                            . 'Inactivo' .
                            '</span>';
                    }
                })
                ->addColumn('driver', function ($staffgroup) {
                    return $staffgroup->driver->name ?? 'Sin conductor';
                })
                ->addColumn('helpers', function ($staffgroup) {
                    if ($staffgroup->helpers->isEmpty()) {
                        return '<div class="bg-light rounded px-2 py-1 text-muted">'
                            . 'Sin ayudantes' .
                            '</div>';
                    }

                    return '<div class="d-flex flex-column gap-1">'
                        . $staffgroup->helpers->map(function ($helper) {
                            return '<div class="bg-light rounded px-2 py-1">'
                                . e($helper->name) .
                                '</div>';
                        })->implode('')
                        . '</div>';
                })
                ->addColumn('actions', function ($staffgroup) {
                    return '<div class="staffgroup-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm staffgroup-action-btn btnVer" data-id="' . $staffgroup->id . '" title="Ver" aria-label="Ver marca">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm staffgroup-action-btn btnEditar" data-id="' . $staffgroup->id .  '" title="Editar" aria-label="Editar marca">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm staffgroup-action-btn btnEliminar" data-id="' . $staffgroup->id  . '" title="Eliminar" aria-label="Eliminar marca">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->editColumn('created_at', function ($staffgroup) {
                    return $staffgroup->created_at
                        ? $staffgroup->created_at->format('d/m/Y H:i:s')
                        : '';
                })
                ->rawColumns(['helpers','driver','state','days','actions'])
                ->toJson();
        }

        return view('admin.staff_groups.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shifts = Shift::pluck('name', 'id');
        $zones = Zone::pluck('name', 'id');
        $vehicles = Vehicle::select(
            'id',
            'code',
            'name',
            'occupats_capacity'
        )->get();
        return view('admin.staff_groups.create', compact('shifts', 'zones', 'vehicles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //necesitamos validar en caso es mismo carro en su mismo horario pero diferentes dias ok
        // mimso carro, turno, zona pero horario iguak enotnces no 
        $validate = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],

            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'integer', 'between:1,7'],

            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')
                    ->where('employee_type_id', 1),
            ],

            'helpers' => ['nullable', 'array'],
            'helpers.*' => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')
                    ->whereNot('employee_type_id', 1),
            ],
        ]);

        try {
            DB::transaction(function () use ($validate) {

                // Registrar grupo
                $group = StaffGroup::create([
                    'name' => $validate['name'],
                    'shift_id' => $validate['shift_id'],
                    'zone_id' => $validate['zone_id'],
                    'vehicle_id' => $validate['vehicle_id'],
                    'driver_id' => $validate['driver_id'] ?? null,
                ]);

                // Registrar días de trabajo
                foreach ($validate['days'] as $day) {
                    $group->days()->create([
                        'day' => $day,
                    ]);
                }

                // Registrar ayudantes
                $helpers = array_values(array_unique(array_filter(
                    $validate['helpers'] ?? [],
                    fn($id) => $id !== null && $id !== ''
                )));

                $group->helpers()->sync($helpers);
            });

            return response()->json([
                'message' => 'Grupo de personal registrado correctamente.',
            ], 201);
        } catch (\Throwable $th) {
            report($th);

            return response()->json([
                'message' => 'El grupo de personal no se pudo registrar.' .$th,
            ], 500);
        }
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
        $shifts = Shift::pluck('name', 'id');
        $zones = Zone::pluck('name', 'id');
        $vehicles = Vehicle::select(
            'id',
            'code',
            'name',
            'occupats_capacity'
        )->get();
        $staffgroups = StaffGroup::with('days')->find($id);
        return view('admin.staff_groups.edit', compact('staffgroups', 'shifts', 'zones', 'vehicles'));
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
        // $staffgroup = StaffGroup::findOrFail($id);
        //$staffgroup->delete();

        return response()->json([
            'message' => 'Grupo de personal eliminado correctamente.',
        ], 200);
    }
}
