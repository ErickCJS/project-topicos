<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Vehicletype;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class VehicletypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::of(Vehicletype::query())
                ->editColumn('created_at', fn ($type) => $type->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn ($type) => $type->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('edit', fn ($type) => '<button type="button" class="btn btn-primary btn-sm btnEditar" data-id="'.$type->id.'" aria-label="Editar tipo"><i class="fas fa-pencil-alt"></i></button>')
                ->addColumn('delete', fn ($type) => '<button type="button" class="btn btn-danger btn-sm btnEliminar" data-id="'.$type->id.'" aria-label="Eliminar tipo"><i class="fas fa-trash"></i></button>')
                ->rawColumns(['edit', 'delete'])
                ->make(true);
        }

        return view('admin.type.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Vehicletype::create($this->validatedData($request));

        return response()->json(['mensaje' => 'Tipo de vehículo registrado correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(Vehicletype::findOrFail($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $type = Vehicletype::findOrFail($id);

        return view('admin.type.edit', compact('type'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $type = Vehicletype::findOrFail($id);
        $type->update($this->validatedData($request, $type));

        return response()->json(['mensaje' => 'Tipo de vehículo actualizado correctamente']);
    }

    /**
     * Remove the specified resource from storage.
     */
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
