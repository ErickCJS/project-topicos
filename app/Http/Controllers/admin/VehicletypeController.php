<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Vehicletype;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class VehicletypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Vehicletype::query())
                ->editColumn('created_at', fn (Vehicletype $type) => $type->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn (Vehicletype $type) => $type->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Vehicletype $type) {
                    return '<div class="vehicle-type-actions">
                                <button type="button" class="btn btn-info btn-sm vehicle-type-action-btn btnVer" data-id="'.$type->id.'" data-url="'.e(route('vehiclestype.show', $type)).'" title="Ver" aria-label="Ver tipo de vehículo">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-warning btn-sm vehicle-type-action-btn btnEditar" data-id="'.$type->id.'" data-url="'.e(route('vehiclestype.edit', $type)).'" title="Editar" aria-label="Editar tipo de vehículo">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-danger btn-sm vehicle-type-action-btn btnEliminar" data-id="'.$type->id.'" data-url="'.e(route('vehiclestype.destroy', $type)).'" title="Eliminar" aria-label="Eliminar tipo de vehículo">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.type.index');
    }

    public function create()
    {
        return view('admin.type.create');
    }

    public function store(Request $request)
    {
        Vehicletype::create($this->validatedData($request));

        return response()->json(['mensaje' => 'Tipo de vehículo registrado correctamente']);
    }

    public function show(string $id)
    {
        return response()->json(Vehicletype::findOrFail($id));
    }

    public function edit(string $id)
    {
        $type = Vehicletype::findOrFail($id);

        return view('admin.type.edit', compact('type'));
    }

    public function update(Request $request, string $id)
    {
        $type = Vehicletype::findOrFail($id);
        $type->update($this->validatedData($request, $type));

        return response()->json(['mensaje' => 'Tipo de vehículo actualizado correctamente']);
    }

    public function destroy(string $id)
    {
        $type = Vehicletype::findOrFail($id);

        if ($type->vehicles()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar este tipo porque tiene vehículos asociados.',
            ], 409);
        }

        $type->delete();

        return response()->json(['mensaje' => 'Tipo de vehículo eliminado correctamente']);
    }

    private function validatedData(Request $request, ?Vehicletype $type = null): array
    {
        $request->merge([
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('vehicletypes', 'name')->ignore($type)],
            'description' => ['nullable', 'string', 'max:100'],
        ], [
            'name.required' => 'El nombre del tipo de vehículo es obligatorio.',
            'name.string' => 'El nombre debe ser un texto.',
            'name.max' => 'El nombre no debe superar los 100 caracteres.',
            'name.unique' => 'Este tipo de vehículo ya se encuentra registrado.',
            'description.string' => 'La descripción debe ser un texto.',
            'description.max' => 'La descripción no debe superar los 100 caracteres.',
        ]);
    }
}
