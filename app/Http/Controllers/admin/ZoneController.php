<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Department;
use App\Models\admin\District;
use App\Models\admin\Province;
use App\Models\admin\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $query = Zone::query()->with('district.province.department');

            return DataTables::eloquent($query)
                ->addColumn('department', fn (Zone $zone) => $zone->district?->province?->department?->name ?? 'Sin departamento')
                ->addColumn('province', fn (Zone $zone) => $zone->district?->province?->name ?? 'Sin provincia')
                ->addColumn('district', fn (Zone $zone) => $zone->district?->name ?? 'Sin distrito')
                ->addColumn('perimeter_status', fn (Zone $zone) => $zone->perimeter ? 'Definido' : 'Sin perímetro')
                ->editColumn('created_at', fn (Zone $zone) => $zone->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn (Zone $zone) => $zone->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Zone $zone) {
                    return '<div class="zone-actions">'
                        .'<button type="button" class="btn btn-info btn-sm zone-action-btn btnVer" data-url="'.e(route('zones.show', $zone)).'" title="Ver" aria-label="Ver zona"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-warning btn-sm zone-action-btn btnEditar" data-url="'.e(route('zones.edit', $zone)).'" title="Editar" aria-label="Editar zona"><i class="bi bi-pencil-square" aria-hidden="true"></i></button>'
                        .'<button type="button" class="btn btn-danger btn-sm zone-action-btn btnEliminar" data-url="'.e(route('zones.destroy', $zone)).'" title="Eliminar" aria-label="Eliminar zona"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                        .'</div>';
                })
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.zones.index');
    }

    public function create()
    {
        return view('admin.zones.create', $this->formData());
    }

    public function store(Request $request)
    {
        Zone::create($this->validatedData($request));

        return response()->json([
            'message' => 'Zona registrada correctamente.',
            'mensaje' => 'Zona registrada correctamente.',
        ], 201);
    }

    public function show(Zone $zone)
    {
        $zone->load('district.province.department');

        return view('admin.zones.show', compact('zone'));
    }

    public function edit(Zone $zone)
    {
        $zone->load('district.province.department');

        return view('admin.zones.edit', $this->formData($zone) + compact('zone'));
    }

    public function update(Request $request, Zone $zone)
    {
        $zone->update($this->validatedData($request, $zone));

        return response()->json([
            'message' => 'Zona actualizada correctamente.',
            'mensaje' => 'Zona actualizada correctamente.',
        ]);
    }

    public function destroy(Zone $zone)
    {
        DB::transaction(fn () => $zone->delete());

        return response()->json([
            'message' => 'Zona eliminada correctamente.',
            'mensaje' => 'Zona eliminada correctamente.',
        ]);
    }

    public function provinces(Department $department)
    {
        return response()->json($department->provinces()
            ->orderBy('name')
            ->get(['id', 'code', 'name']));
    }

    public function districts(Province $province)
    {
        return response()->json($province->districts()
            ->orderBy('name')
            ->get(['id', 'code', 'name']));
    }

    private function formData(?Zone $zone = null): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->get(['id', 'code', 'name']),
            'zone' => $zone,
        ];
    }

    private function validatedData(Request $request, ?Zone $zone = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('zones', 'name')
                    ->where(fn ($query) => $query->where('district_id', $request->input('district_id')))
                    ->ignore($zone?->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'perimeter' => ['required'],
        ], [
            'name.required' => 'El nombre de la zona es obligatorio.',
            'name.string' => 'El nombre de la zona debe ser texto.',
            'name.max' => 'El nombre de la zona no debe superar los 120 caracteres.',
            'name.unique' => 'Ya existe una zona con ese nombre en el distrito seleccionado.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe superar los 2000 caracteres.',
            'department_id.required' => 'Seleccione un departamento.',
            'department_id.exists' => 'El departamento seleccionado no existe.',
            'province_id.required' => 'Seleccione una provincia.',
            'province_id.exists' => 'La provincia seleccionada no existe.',
            'district_id.required' => 'Seleccione un distrito.',
            'district_id.exists' => 'El distrito seleccionado no existe.',
            'perimeter.required' => 'Dibuje el perímetro de la zona.',
        ]);

        $district = District::query()->with('province')->find($data['district_id']);
        if (! $district || (int) $district->province_id !== (int) $data['province_id']) {
            $this->validationError('province_id', 'El distrito no pertenece a la provincia seleccionada.');
        }
        if (! $district->province || (int) $district->province->department_id !== (int) $data['department_id']) {
            $this->validationError('department_id', 'La provincia no pertenece al departamento seleccionado.');
        }

        $data['perimeter'] = $this->validatedPerimeter($data['perimeter']);

        return $data;
    }

    /** @return array{type:string,coordinates:array<int,array<int,array{0:float,1:float}>>} */
    private function validatedPerimeter(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }
        if (is_array($value) && ($value['type'] ?? null) === 'Feature') {
            $value = $value['geometry'] ?? null;
        }
        if (! is_array($value) || ($value['type'] ?? null) !== 'Polygon' || ! isset($value['coordinates'])) {
            $this->validationError('perimeter', 'El perímetro debe ser un GeoJSON Polygon válido.');
        }

        $rings = $value['coordinates'];
        if (! is_array($rings) || count($rings) !== 1 || ! is_array($rings[0])) {
            $this->validationError('perimeter', 'El perímetro debe contener un único anillo poligonal.');
        }

        $ring = $rings[0];
        if (count($ring) < 4) {
            $this->validationError('perimeter', 'El polígono debe tener al menos tres vértices distintos.');
        }

        $coordinates = [];
        foreach ($ring as $coordinate) {
            if (! is_array($coordinate) || count($coordinate) < 2 || ! is_numeric($coordinate[0]) || ! is_numeric($coordinate[1])) {
                $this->validationError('perimeter', 'Cada coordenada debe tener el formato [longitud, latitud].');
            }

            $longitude = (float) $coordinate[0];
            $latitude = (float) $coordinate[1];
            if (! is_finite($longitude) || ! is_finite($latitude) || $longitude < -180 || $longitude > 180 || $latitude < -90 || $latitude > 90) {
                $this->validationError('perimeter', 'Las coordenadas deben estar dentro de los rangos geográficos válidos.');
            }
            $coordinates[] = [$longitude, $latitude];
        }

        $first = $coordinates[0];
        $last = $coordinates[count($coordinates) - 1];
        if (abs($first[0] - $last[0]) > 0.000000001 || abs($first[1] - $last[1]) > 0.000000001) {
            $this->validationError('perimeter', 'El anillo del polígono debe estar cerrado.');
        }

        $distinct = [];
        foreach (array_slice($coordinates, 0, -1) as $coordinate) {
            $distinct[$coordinate[0].':'.$coordinate[1]] = true;
        }
        if (count($distinct) < 3) {
            $this->validationError('perimeter', 'El polígono debe tener al menos tres vértices distintos.');
        }

        $area = 0.0;
        foreach ($coordinates as $index => $coordinate) {
            $next = $coordinates[($index + 1) % count($coordinates)];
            $area += ($coordinate[0] * $next[1]) - ($next[0] * $coordinate[1]);
        }
        if (abs($area) < 0.000000000001) {
            $this->validationError('perimeter', 'El polígono debe encerrar un área mayor que cero.');
        }

        return ['type' => 'Polygon', 'coordinates' => [$coordinates]];
    }

    private function validationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
