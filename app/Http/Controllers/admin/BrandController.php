<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use \Carbon\Carbon;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
public function index(Request $request)
{
    if ($request->ajax()) {

        $brands = Brand::all();

        return DataTables::of($brands)

            ->addColumn('logo', function ($brand) {

                $logo = empty($brand->logo)
                    ? asset('storage/images/no_logo.png')
                    : asset($brand->logo);

                return '<button type="button" class="brand-logo-preview" data-image="' . e($logo) . '" data-brand="' . e($brand->name) . '" title="Ver logo de ' . e($brand->name) . '" aria-label="Ver logo de ' . e($brand->name) . '">
                            <img src="' . e($logo) . '" alt="Logo de ' . e($brand->name) . '">
                        </button>';
            })
            ->addColumn('created_at', function ($brand) {
                return Carbon::parse($brand->created_at)->format('d/m/Y H:i:s');
            })
            ->addColumn('updated_at', function ($brand) {
                return Carbon::parse($brand->updated_at)->format('d/m/Y H:i:s');
            })
            ->addColumn('actions', function ($brand) {
                return '<div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-primary btn-sm btnEditar" data-id="' . $brand->id . '" title="Editar" aria-label="Editar marca">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm btnEliminar" data-id="' . $brand->id . '" title="Eliminar" aria-label="Eliminar marca">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>';
            })

            ->rawColumns([
                'logo',
                'actions'
            ])

            ->make(true);
    }

    return view('admin.brands.index');
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.brands.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $logo = '';
            $request->validate([
                'name' => 'required|unique:brands,name'
            ],[
                'name.required' => 'El nombre de la marca es obligatorio.',
                'name.unique'=> ' esta marca ya se encuentra registrada.'
            ]);

            if ($request->hasFile('logo')) {
                $image = $request->file('logo')->store('brand_logo', 'public');
                $logo = Storage::url($image);
            }
            Brand::create([
                'name' => $request->name,
                'logo' => $logo,
                'description' => $request->descriptopn
            ]);
            return response()->json(["mensaje"=> "marca registrada correctamente"], 200);
        } catch (\Throwable $th) {
            return response()->json(["mensaje"=> "error de registro: ". $th->getMessage()], 500);
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
        $brand = Brand::find($id);
        return view('admin.brands.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {

            $brand = Brand::find($id);

            $request->validate([
                'name' => 'unique:brands,name,' . $id,
            ],[
                'name.required' => 'El nombre de la marca es obligatorio.',
                'name.unique' => ' esta marca ya se encuentra registrada.'
            ]);

            $logo = $brand->logo;

            if ($request->hasFile('logo')) {
                $image = $request->file('logo')->store('brand_logo', 'public');
                $logo = Storage::url($image);
            } 
            $brand->update([
                'name' => $request->name,
                'logo' => $logo,
                'description' => $request->description
            ]);
            $brand->update();
            return response()->json(["mensaje" => "marca actualizada correctamente"], 200);
        } catch (\Throwable $th) {
            return response()->json(["mensaje" => "error de actualización" . $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $brand = Brand::with(['models', 'vehicles'])->findOrFail($id);

            if($brand->models()->exists() || $brand->vehicles()->exists()){
                return response()->json(["mensaje" => "No se puede eliminar esta marca porque tiene relación con otras tablas"], 409);
            }

            $brand->delete();
            return response()->json(["mensaje" => "Marca eliminada correctamente"], 200);
        } catch (\Throwable $th) {
            return response()->json(["mensaje" => "Error de eliminación" . $th->getMessage()], 500);
        }
    }
}
