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
            $models = Brandmodel::with('brand')->get();

            return DataTables::of($models)
                ->addColumn('brand_name', function ($model) {
                    return $model->brand?->name ?? '';
                })
                ->editColumn('created_at', function ($model) {
                    return $model->created_at?->format('d/m/Y H:i:s');
                })
                ->editColumn('updated_at', function ($model) {
                    return $model->updated_at?->format('d/m/Y H:i:s');
                })
                ->addColumn('actions', function ($model) {
                    return '<div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm btnEditar"
                            data-id="' . $model->id . '" title="Editar">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btnEliminar"
                            data-id="' . $model->id . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
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
        $data = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        Brandmodel::create($data);

        return response()->json([
            'message' => 'Modelo registrado correctamente'
        ]);
    }

    public function edit(string $id)
    {
        $model = Brandmodel::findOrFail($id);
        $brands = Brand::orderBy('name')->pluck('name', 'id');

        return view('admin.model.edit', compact('model', 'brands'));
    }

    public function update(Request $request, string $id)
    {
        $model = Brandmodel::findOrFail($id);

        $data = $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
        ]);

        $model->update($data);

        return response()->json([
            'message' => 'Modelo actualizado correctamente'
        ]);
    }

    public function destroy(string $id)
    {
        $model = Brandmodel::findOrFail($id);

        if ($model->vehicles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar el modelo porque tiene vehículos relacionados'
            ], 409);
        }

        $model->delete();

        return response()->json([
            'message' => 'Modelo eliminado correctamente'
        ]);
    }
}