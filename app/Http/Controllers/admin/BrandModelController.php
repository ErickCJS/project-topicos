<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Brand;
use App\Models\admin\Brandmodel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BrandModelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $models = Brandmodel::query()->with('brand');

            return DataTables::eloquent($models)
                ->addColumn('brand_name', function (Brandmodel $model) {
                    return $model->brand?->name ?? '';
                })
                ->editColumn('created_at', function (Brandmodel $model) {
                    return $model->created_at?->format('d/m/Y H:i:s');
                })
                ->editColumn('updated_at', function (Brandmodel $model) {
                    return $model->updated_at?->format('d/m/Y H:i:s');
                })
                ->addColumn('actions', function (Brandmodel $model) {
                    return '<div class="model-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm model-action-btn btnVer" data-id="'.$model->id.'" data-url="'.e(route('brandsmodel.show', $model)).'" title="Ver" aria-label="Ver modelo">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm model-action-btn btnEditar" data-id="'.$model->id.'" data-url="'.e(route('brandsmodel.edit', $model)).'" title="Editar" aria-label="Editar modelo">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm model-action-btn btnEliminar" data-id="'.$model->id.'" data-url="'.e(route('brandsmodel.destroy', $model)).'" title="Eliminar" aria-label="Eliminar modelo">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['actions'])
                ->toJson();
        }

        return view('admin.model.index');
    }

    public function create()
    {
        $brands = Brand::orderBy('name')->pluck('name', 'id');

        return view('admin.model.create', compact('brands'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        Brandmodel::create($data);

        return response()->json([
            'message' => 'Modelo registrado correctamente',
        ]);
    }

    public function show(Brandmodel $brandsmodel)
    {
        $model = $brandsmodel->load('brand');

        return view('admin.model.show', compact('model'));
    }

    public function edit(Brandmodel $brandsmodel)
    {
        $model = $brandsmodel;
        $brands = Brand::orderBy('name')->pluck('name', 'id');

        return view('admin.model.edit', compact('model', 'brands'));
    }

    public function update(Request $request, Brandmodel $brandsmodel)
    {
        $data = $this->validatedData($request);

        $brandsmodel->update($data);

        return response()->json([
            'message' => 'Modelo actualizado correctamente',
        ]);
    }

    public function destroy(Brandmodel $brandsmodel)
    {
        if ($brandsmodel->vehicles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el modelo porque tiene vehículos relacionados',
            ], 409);
        }

        $brandsmodel->delete();

        return response()->json([
            'message' => 'Modelo eliminado correctamente',
        ]);
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'brand_id' => 'required|integer|exists:brands,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ], [
            'brand_id.required' => 'Seleccione una marca.',
            'brand_id.integer' => 'La marca seleccionada no es válida.',
            'brand_id.exists' => 'La marca seleccionada no existe.',
            'name.required' => 'El nombre del modelo es obligatorio.',
            'name.string' => 'El nombre del modelo debe ser texto.',
            'name.max' => 'El nombre del modelo no debe superar los 100 caracteres.',
            'code.string' => 'El código debe ser texto.',
            'code.max' => 'El código no debe superar los 50 caracteres.',
            'description.string' => 'La descripción debe ser texto.',
        ]);
    }
}
