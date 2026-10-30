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

    private function validarGrupoPersonal(Request $request, ?StaffGroup $group = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('staff_groups', 'name')->ignore($group?->id)],
            'shift_id' => ['required', 'integer', 'exists:shifts,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'state' => ['sometimes', 'boolean'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'integer', 'distinct', 'between:1,7'],
            'driver_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('employee_type_id', 1)->where('status', 'Activo')],
            'helpers' => ['required', 'array', 'min:1'],
            'helpers.*' => ['nullable', 'integer', 'distinct', Rule::exists('employees', 'id')->whereNot('employee_type_id', 1)->where('status', 'Activo')],
        ], [
            'name.unique' => 'Ya existe un grupo de personal con ese nombre.',
            'driver_id.required' => 'Debe seleccionar un conductor para el grupo.',
            'driver_id.exists' => 'Seleccione un conductor activo con el cargo correspondiente.',
            'helpers.*.exists' => 'Seleccione ayudantes activos con el cargo correspondiente.',
            'helpers.required' => 'Debe seleccionar al menos un ayudante para el grupo.',
            'helpers.min' => 'Debe seleccionar al menos un ayudante para el grupo.',
            'helpers.*.distinct' => 'No puede seleccionar al mismo ayudante más de una vez.',
            'days.required' => 'Seleccione al menos un día de trabajo.',
            'days.min' => 'Seleccione al menos un día de trabajo.',
            'days.*.distinct' => 'No puede repetir los días de trabajo.',
        ], ['name' => 'nombre del grupo', 'shift_id' => 'turno', 'zone_id' => 'zona', 'vehicle_id' => 'vehículo', 'days' => 'días de trabajo', 'driver_id' => 'conductor', 'helpers' => 'ayudantes', 'state' => 'estado']);

        $data['helpers'] = array_values(array_filter($data['helpers'] ?? [], fn ($id) => $id !== null && $id !== ''));
        if (! $data['helpers']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['helpers' => 'Debe seleccionar al menos un ayudante para el grupo.']);
        }
        $data['state'] = (bool) ($data['state'] ?? $group?->state ?? true);
        $vehicle = Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
        Employee::whereIn('id', array_merge([$data['driver_id']], $data['helpers']))->orderBy('id')->lockForUpdate()->get();
        if (1 + count($data['helpers']) > (int) $vehicle->occupats_capacity) {
            throw \Illuminate\Validation\ValidationException::withMessages(['helpers' => 'El conductor y los ayudantes superan la capacidad de ocupantes del vehículo.']);
        }
        $this->validarDisponibilidad($data, $group?->id);

        return $data;
    }

    private function validarDisponibilidad(array $data, ?int $groupId = null): void
    {
        if (! $data['state']) {
            return;
        }
        $groups = StaffGroup::with('helpers')->where('state', true)
            ->where('shift_id', $data['shift_id'])
            ->when($groupId, fn ($query) => $query->whereKeyNot($groupId))
            ->whereHas('days', fn ($query) => $query->whereIn('day', $data['days']))->get();
        foreach ($groups as $other) {
            if ((int) $other->vehicle_id === (int) $data['vehicle_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['vehicle_id' => 'El vehículo ya está asignado al grupo '.$other->name.' en el mismo turno y uno de los días seleccionados.']);
            }
            if ((int) $other->driver_id === (int) $data['driver_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['driver_id' => 'El conductor ya está asignado al grupo '.$other->name.' en el mismo turno y uno de los días seleccionados.']);
            }
            if ($other->helpers->pluck('id')->intersect($data['helpers'])->isNotEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['helpers' => 'Un ayudante ya está asignado al grupo '.$other->name.' en el mismo turno y uno de los días seleccionados.']);
            }
        }
    }

    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            $data = $this->validarGrupoPersonal($request);
            $group = StaffGroup::create(collect($data)->except(['days', 'helpers'])->all());
            foreach ($data['days'] as $day) {
                $group->days()->create(['day' => $day]);
            }
            $group->helpers()->sync($data['helpers']);
        }, 3);

        return response()->json(['message' => 'Grupo de personal registrado correctamente.'], 201);
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
        DB::transaction(function () use ($request, $id) {
            $group = StaffGroup::whereKey($id)->lockForUpdate()->firstOrFail();
            $data = $this->validarGrupoPersonal($request, $group);
            $group->update(collect($data)->except(['days', 'helpers'])->all());
            $group->days()->delete();
            foreach ($data['days'] as $day) {
                $group->days()->create(['day' => $day]);
            }
            $group->helpers()->sync($data['helpers']);
        }, 3);

        return response()->json(['message' => 'Grupo de personal actualizado correctamente.']);
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
        $grupos = StaffGroup::with('helpers:id')->where('state', true)
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

        // Same availability and active-person rules used when saving.
        $conductores = Employee::where('employee_type_id', 1)->where('status', 'Activo')
            ->whereNotIn('id', $conductoresOcupados)->get()
            ->mapWithKeys(fn ($person) => [$person->id => $person->dni.' — '.trim($person->first_name.' '.$person->last_name)]);
        $ayudantes = Employee::where('employee_type_id', '!=', 1)->where('status', 'Activo')
            ->whereNotIn('id', $ayudantesOcupados)->get()
            ->mapWithKeys(fn ($person) => [$person->id => $person->dni.' — '.trim($person->first_name.' '.$person->last_name)]);

        return response()->json([
            'conductores' => $conductores,
            'ayudantes' => $ayudantes,
        ]);
    }
}
