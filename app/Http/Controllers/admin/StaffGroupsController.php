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
            $vigenteContract = fn ($query) => $query->where('is_active', true)
                ->whereNull('cancelled_at')
                ->whereDate('start_date', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', today()));
            $vacationToday = fn ($query) => $query->covering(today()->toDateString());

            $query = StaffGroup::query()
                ->join('shifts', 'staff_groups.shift_id', '=', 'shifts.id')
                ->join('zones', 'staff_groups.zone_id', '=', 'zones.id')
                ->join('vehicles', 'staff_groups.vehicle_id', '=', 'vehicles.id')
                ->with([
                    'days',
                    'driver.contracts' => $vigenteContract,
                    'driver.vacations' => $vacationToday,
                    'helpers.contracts' => $vigenteContract,
                    'helpers.vacations' => $vacationToday,
                ])
                ->select([
                    'staff_groups.*',
                    'shifts.name as shift_name',
                    'zones.name as zone_name',
                    'vehicles.name as vehicle_name',
                    'vehicles.occupats_capacity as vehicle_capacity',
                ]);

            $filterByEmployee = fn ($groups, string $relation, string $keyword) => $groups
                ->whereHas($relation, fn ($employees) => $employees->where(
                    fn ($names) => $names->where('employees.first_name', 'like', '%'.$keyword.'%')
                        ->orWhere('employees.last_name', 'like', '%'.$keyword.'%')
                ));

            return DataTables::eloquent($query)
                ->filterColumn('driver', fn ($groups, $keyword) => $filterByEmployee($groups, 'driver', $keyword))
                ->filterColumn('helpers', fn ($groups, $keyword) => $filterByEmployee($groups, 'helpers', $keyword))
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
                    if (! $staffgroup->driver) {
                        return $this->reassignmentWarning('Asignar conductor');
                    }

                    if ($this->staffMemberNeedsReplacement($staffgroup->driver)) {
                        return $this->reassignmentWarning(
                            'Reasignar conductor: '.trim($staffgroup->driver->first_name.' '.$staffgroup->driver->last_name)
                        );
                    }

                    return e(trim($staffgroup->driver->first_name . ' ' . $staffgroup->driver->last_name));
                })
                ->addColumn('helpers', function ($staffgroup) {
                    $helperRows = $staffgroup->helpers->map(function ($helper) {
                            if ($this->staffMemberNeedsReplacement($helper)) {
                                return $this->reassignmentWarning(
                                    'Reasignar ayudante: '.trim($helper->first_name.' '.$helper->last_name)
                                );
                            }

                            return '<div class="bg-light rounded px-2 py-1">'
                                . e($helper->first_name) . ' ' . e($helper->last_name).
                                '</div>';
                        })->all();
                    $missingHelpers = max(0, (int) $staffgroup->vehicle_capacity - 1 - $staffgroup->helpers->count());

                    for ($index = 0; $index < $missingHelpers; $index++) {
                        $helperRows[] = $this->reassignmentWarning('Asignar ayudante');
                    }

                    return '<div class="d-flex flex-column gap-1">'.implode('', $helperRows).'</div>';
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
            'helpers' => ['sometimes', 'nullable', 'array'],
            'helpers.*' => ['nullable', 'integer', 'distinct', Rule::exists('employees', 'id')->whereNot('employee_type_id', 1)->where('status', 'Activo')],
        ], [
            'name.unique' => 'Ya existe un grupo de personal con ese nombre.',
            'driver_id.required' => 'Debe seleccionar un conductor para el grupo.',
            'driver_id.exists' => 'Seleccione un conductor activo con el cargo correspondiente.',
            'helpers.*.exists' => 'Seleccione ayudantes activos con el cargo correspondiente.',
            'helpers.*.distinct' => 'No puede seleccionar al mismo ayudante más de una vez.',
            'days.required' => 'Seleccione al menos un día de trabajo.',
            'days.min' => 'Seleccione al menos un día de trabajo.',
            'days.*.distinct' => 'No puede repetir los días de trabajo.',
        ], ['name' => 'nombre del grupo', 'shift_id' => 'turno', 'zone_id' => 'zona', 'vehicle_id' => 'vehículo', 'days' => 'días de trabajo', 'driver_id' => 'conductor', 'helpers' => 'ayudantes', 'state' => 'estado']);

        $data['helpers'] = array_values(array_filter($data['helpers'] ?? [], fn ($id) => $id !== null && $id !== ''));
        $data['state'] = (bool) ($data['state'] ?? $group?->state ?? true);
        $vehicle = Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
        Employee::whereIn('id', array_merge([$data['driver_id']], $data['helpers']))->orderBy('id')->lockForUpdate()->get();
        if (1 + count($data['helpers']) > (int) $vehicle->occupats_capacity) {
            throw \Illuminate\Validation\ValidationException::withMessages(['helpers' => 'El conductor y los ayudantes superan la capacidad de ocupantes del vehículo.']);
        }
        $this->validarPersonalConContratoVigente($data);
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
            'vehiculo' => ['nullable', 'integer', 'exists:vehicles,id'],
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
            ->get(['id', 'driver_id', 'vehicle_id']);

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
            ->where('status', 'Activo')
            ->withVigenteContract()
            ->whereDoesntHave('vacations', fn ($query) => $query->covering(today()->toDateString()))
            ->whereNotIn('id', $conductoresOcupados)
            ->get(['id', 'dni', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Employee $employee) => [
                $employee->id => $employee->dni.' — '.trim($employee->first_name.' '.$employee->last_name),
            ]);

        // Ayudantes disponibles.
        $ayudantes = Employee::where('employee_type_id', '!=', 1)
            ->where('status', 'Activo')
            ->withVigenteContract()
            ->whereDoesntHave('vacations', fn ($query) => $query->covering(today()->toDateString()))
            ->whereNotIn('id', $ayudantesOcupados)
            ->get(['id', 'dni', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Employee $employee) => [
                $employee->id => $employee->dni.' — '.trim($employee->first_name.' '.$employee->last_name),
            ]);

        $vehiculosOcupados = $grupos->pluck('vehicle_id')->filter()->unique()->values();
        $vehiculos = Vehicle::query()->whereNotIn('id', $vehiculosOcupados)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'occupats_capacity'])
            ->mapWithKeys(fn (Vehicle $vehicle) => [$vehicle->id => [
                'label' => $vehicle->code.' — '.$vehicle->name.' (Capacidad: '.$vehicle->occupats_capacity.')',
                'capacity' => $vehicle->occupats_capacity,
            ]]);

        return response()->json([
            'conductores' => $conductores,
            'ayudantes' => $ayudantes,
            'vehiculos' => $vehiculos,
        ]);
    }

    private function validarPersonalConContratoVigente(array $datos): void
    {
        $driverId = $datos['driver_id'] ?? null;
        if ($driverId && ! Employee::query()
            ->whereKey($driverId)
            ->where('status', 'Activo')
            ->withVigenteContract()
            ->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'driver_id' => 'El conductor debe estar activo y tener un contrato vigente.',
            ]);
        }

        $helperIds = collect($datos['helpers'] ?? [])
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->unique()
            ->values();

        if ($helperIds->isNotEmpty()) {
            $eligibleHelpers = Employee::query()
                ->whereIn('id', $helperIds)
                ->where('employee_type_id', '!=', 1)
                ->where('status', 'Activo')
                ->withVigenteContract()
                ->count();

            if ($eligibleHelpers !== $helperIds->count()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'helpers' => 'Todos los ayudantes deben estar activos y tener un contrato vigente.',
                ]);
            }
        }
    }

    private function staffMemberNeedsReplacement(Employee $employee): bool
    {
        return $employee->status !== 'Activo'
            || $employee->contracts->isEmpty()
            || $employee->vacations->isNotEmpty();
    }

    private function reassignmentWarning(string $message): string
    {
        return '<span class="badge rounded-pill bg-light text-secondary border d-inline-flex align-items-center gap-1">'
            . '<i class="bi bi-exclamation-triangle-fill text-warning" aria-hidden="true"></i>'
            . e($message) . '</span>';
    }
}
