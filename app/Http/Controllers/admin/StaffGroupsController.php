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
                        return '<span class="badge rounded-pill bg-secondary">'
                            . 'Inactivo' .
                            '</span>';
                    }
                })
                ->addColumn('driver', function ($staffgroup) {
                    return $staffgroup->driver
                        ? trim($staffgroup->driver->first_name . ' ' . $staffgroup->driver->last_name)
                        : 'Sin conductor';
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
                                . e($helper->first_name) . ' ' . e($helper->last_name).
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

    private function validarGrupoPersonal(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:staff_groups,name'],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'state' => ['nullable', 'boolean'],

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
        ], [
            'name.unique' => 'Ya existe un grupo de personal con ese nombre.',
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',

            'shift_id.required' => 'Debes seleccionar un turno.',
            'shift_id.exists' => 'El turno seleccionado no existe.',

            'zone_id.required' => 'Debes seleccionar una zona.',
            'zone_id.exists' => 'La zona seleccionada no existe.',

            'vehicle_id.required' => 'Debes seleccionar un vehículo.',
            'vehicle_id.exists' => 'El vehículo seleccionado no existe.',

            'state.required' => 'Debes indicar el estado del grupo.',
            'state.boolean' => 'El estado seleccionado no es válido.',

            'days.required' => 'Selecciona al menos un día de trabajo.',
            'days.min' => 'Selecciona al menos un día de trabajo.',
            'days.*.integer' => 'Uno de los días seleccionados no es válido.',
            'days.*.between' => 'Los días deben estar comprendidos entre 1 y 7.',

            'driver_id.exists' => 'El empleado seleccionado no es un conductor válido.',

            'helpers.*.integer' => 'Uno de los ayudantes seleccionados no es válido.',
            'helpers.*.exists' => 'Uno de los empleados seleccionados no es un ayudante válido.',
        ], [
            'name' => 'nombre del grupo',
            'shift_id' => 'turno',
            'zone_id' => 'zona',
            'vehicle_id' => 'vehículo',
            'state' => 'estado',
            'days' => 'días de trabajo',
            'driver_id' => 'conductor',
            'helpers' => 'ayudantes',
        ]);
    }

    private function validarDisponibilidad(
        array $datos,
        ?int $grupoId = null
    ): void {
        //validemos que en ese turno esa zona y ese vehiculo no este siendo utilizado en ese día de trabajo

        $conflicto = StaffGroup::where('shift_id', $datos['shift_id'])
            ->where('zone_id', $datos['zone_id'])
            ->where('vehicle_id', $datos['vehicle_id'])
            ->when($grupoId, fn($query) => $query->where('id', '!=', $grupoId))
            ->whereHas('days', function ($query) use ($datos) {
                $query->whereIn('day', $datos['days']);
            })
            ->exists();

        if ($conflicto) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'días' => 'El vehículo ya está asignado a ese turno y zona en uno o más de los días seleccionados.',
            ]);
        }
    }

    public function store(Request $request)
    {
        //necesitamos validar en caso es mismo carro en su mismo horario pero diferentes dias ok
        // mismo carro, turno, zona pero horario iguak enotnces no 
        $validate = $this->validarGrupoPersonal($request);
        $this->validarDisponibilidad($validate);
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
                'message' => 'El grupo de personal no se pudo registrar.',
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $staffgroup = StaffGroup::with([
            'shift',
            'zone',
            'vehicle',
            'days',
            'driver',
            'helpers',
        ])->findOrFail($id);

        return view('admin.staff_groups.show', compact('staffgroup'));
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

        $staffgroups = StaffGroup::with([
            'days',
            'driver',
            'helpers',
        ])->findOrFail($id);

        return view('admin.staff_groups.edit', compact(
            'staffgroups',
            'shifts',
            'zones',
            'vehicles'
        ));
    }


    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, string $id)
    {
        $staffgroup = StaffGroup::findOrFail($id);

        // Validar los datos del formulario
        $datos = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('staff_groups', 'name')->ignore($staffgroup->id),
            ],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'state' => ['sometimes', 'boolean'],

            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],

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
                'distinct',
                Rule::exists('employees', 'id')
                    ->whereNot('employee_type_id', 1),
            ],
        ], [
            'name.required' => 'El nombre del grupo es obligatorio.',
            'name.max' => 'El nombre no puede superar los 100 caracteres.',
            'name.unique' => 'Ya existe un grupo de personal con ese nombre.',

            'shift_id.required' => 'Debes seleccionar un turno.',
            'shift_id.exists' => 'El turno seleccionado no existe.',

            'zone_id.required' => 'Debes seleccionar una zona.',
            'zone_id.exists' => 'La zona seleccionada no existe.',

            'vehicle_id.required' => 'Debes seleccionar un vehículo.',
            'vehicle_id.exists' => 'El vehículo seleccionado no existe.',

            'days.required' => 'Selecciona al menos un día de trabajo.',
            'days.min' => 'Selecciona al menos un día de trabajo.',
            'days.*.distinct' => 'No puedes seleccionar un día más de una vez.',
            'days.*.between' => 'Los días deben estar comprendidos entre 1 y 7.',

            'driver_id.exists' => 'El empleado seleccionado no es un conductor válido.',
            'helpers.*.distinct' => 'No puedes seleccionar al mismo ayudante más de una vez.',
            'helpers.*.exists' => 'Uno de los empleados seleccionados no es un ayudante válido.',
        ]);

        // Comprobar conflictos de turno, zona, vehículo y días,
        // excluyendo el propio grupo que se está editando.
        $this->validarDisponibilidad($datos, $staffgroup->id);

        DB::transaction(function () use ($datos, $staffgroup) {
            $staffgroup->update([
                'name' => $datos['name'],
                'shift_id' => $datos['shift_id'],
                'zone_id' => $datos['zone_id'],
                'vehicle_id' => $datos['vehicle_id'],
                'driver_id' => $datos['driver_id'] ?? null,
                'state' => (int) $datos['state'],
            ]);

            // Reemplazar los días de trabajo.
            $staffgroup->days()->delete();

            foreach ($datos['days'] as $day) {
                $staffgroup->days()->create([
                    'day' => $day,
                ]);
            }

            // Sincronizar los ayudantes.
            $helpers = array_values(array_unique(array_filter(
                $datos['helpers'] ?? [],
                fn($id) => $id !== null && $id !== ''
            )));

            $staffgroup->helpers()->sync($helpers);
        });

        return response()->json([
            'message' => 'Grupo de personal actualizado correctamente.',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $staffgroup = StaffGroup::findOrFail($id);
        $staffgroup->delete();

        return response()->json([
            'message' => 'Grupo de personal eliminado correctamente.',
        ], 200);
    }



    public function helpers(Request $request)
    {
        $datos = $request->validate([
            'turnos' => ['required', 'integer', 'exists:shifts,id'],
            'zona' => ['required', 'integer', 'exists:zones,id'],
            'vehiculo' => ['required', 'integer', 'exists:vehicles,id'],
            'dias' => ['required', 'array', 'min:1'],
            'dias.*' => ['required', 'integer', 'between:1,7'],
            'id_staff' => ['nullable', 'integer', 'exists:staff_groups,id'],
        ]);

        // Buscar grupos que ocupan personal, excluyendo el grupo editado.
        $grupos = StaffGroup::with('helpers:id')
            ->where('shift_id', $datos['turnos'])
            ->when(
                !empty($datos['id_staff']),
                fn($query) => $query->where('id', '!=', $datos['id_staff'])
            )
            ->whereHas('days', function ($query) use ($datos) {
                $query->whereIn('day', $datos['dias']);
            })
            ->get(['id', 'driver_id']);

        // Conductores ocupados en otros grupos.
        $conductoresOcupados = $grupos
            ->pluck('driver_id')
            ->filter()
            ->unique()
            ->values();

        // Ayudantes ocupados en otros grupos.
        $ayudantesOcupados = $grupos
            ->flatMap(fn($grupo) => $grupo->helpers->pluck('id'))
            ->unique()
            ->values();

        // Conductores disponibles.
        $conductores = Employee::where('employee_type_id', 1)
            ->whereNotIn('id', $conductoresOcupados)
            ->selectRaw(
                "id, CONCAT(dni, ' — ', CONCAT_WS(' ', first_name, last_name)) AS nombre_completo"
            )
            ->pluck('nombre_completo', 'id');

        // Ayudantes disponibles.
        $ayudantes = Employee::where('employee_type_id', '!=', 1)
            ->whereNotIn('id', $ayudantesOcupados)
            ->selectRaw(
                "id, CONCAT(dni, ' — ', CONCAT_WS(' ', first_name, last_name)) AS nombre_completo"
            )
            ->pluck('nombre_completo', 'id');

        return response()->json([
            'conductores' => $conductores,
            'ayudantes' => $ayudantes,
        ]);
    }
}
