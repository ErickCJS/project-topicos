<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehiclesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $vehicle = Vehicle::select(
        'vehicles.id',
        'vehicles.code',
        'vehicle.plate',
        'b.name as brand',
        'm.name as model',
        'c.name as color',
        'vt.name as type'
        )
        ->join('brands as b', 'vehicles_brand_id', '=', 'b.id')
        ->join('brandmodels as m', 'vehicles_brand_id', '=', 'm.id')
        ->join('colors as b', 'vehicles_brand_id', '=', 'c.id')
        ->join('vehicletypes as vt', 'vehicles_typeid, '=', 'vt.id');

    if ($request->ajax()) {


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

                return '<form action="' . route('brands.destroy', $brand->id) . '"
                        method="POST"
                        class="frmEliminar">

                        ' . csrf_field() . '

                        <input type="hidden"
                               name="_method"
                               value="DELETE">

                        <button type="submit"
                                class="btn btn-sm btn-danger">
                            <i class="bi bi-trash3"></i>
                        </button>

                    </form>';
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
