<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

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

                return '<img src="' . $logo . '"
                        style="width:70px; height:50px; object-fit:contain;">';
            })

            ->addColumn('edit', function ($brand) {

                return '<button
                            class="btn btn-primary btn-sm btnEditar"
                            data-id="' . $brand->id . '">
                            <i class="bi bi-pencil-square"></i>
                        </button>';
            })

            ->addColumn('delete', function ($brand) {

                return '<button
                            class="btn btn-sm btn-danger btnEliminar"
                            data-id="' . $brand->id . '">
                            <i class="bi bi-trash3"></i>
                        </button>';
            })

            ->rawColumns([
                'logo',
                'edit',
                'delete'
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
                'name' => 'unique:brands'
            ]);
            if ($request->logo != '') {
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
            return response()->json(["mensaje"=> "error de registro". $th->getMessage()], 500);
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
            ]);

            $logo = '';

            if ($request->logo != '') {
                $image = $request->file('logo')->store('brand_logo', 'public');
                $logo = Storage::url($image);

                $brand->update([
                    'name' => $request->name,
                    'logo' => $logo,
                    'description' => $request->description
                ]);
            } else {
                /*$brand->update([
                    'name' => $request->name,
                    'description' => $request->description
                ]);*/
                $brand->update($request->except('logo'));
            }
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
            $brand = Brand::find($id);
            $brand->delete();
            return response()->json(["mensaje" => "Marca eliminada correctamente"], 200);
        } catch (\Throwable $th) {
            return response()->json(["mensaje" => "Error de eliminación"], 200);
        }
    }
}
