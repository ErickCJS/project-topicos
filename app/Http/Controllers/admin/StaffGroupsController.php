<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Shift;
use App\Models\admin\Vehicle;
use Illuminate\Http\Request;

class StaffGroupsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return DataTables::eloquent(Brand::query())
                ->addColumn('logo', function (Brand $brand) {
                    $logo = $this->logoUrl($brand);

                    return '<button type="button" class="brand-logo-preview" data-image="' . e($logo) . '" data-brand="' . e($brand->name) . '" title="Ver logo de ' . e($brand->name) . '" aria-label="Ver logo de ' . e($brand->name) . '">
                                <img src="' . e($logo) . '" alt="Logo de ' . e($brand->name) . '" loading="lazy" decoding="async">
                            </button>';
                })
                ->editColumn('created_at', fn(Brand $brand) => $brand->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn(Brand $brand) => $brand->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (Brand $brand) {
                    return '<div class="brand-actions">
                                <button type="button" class="btn btn-outline-primary btn-sm brand-action-btn btnVer" data-id="' . $brand->id . '" data-url="' . e(route('brands.show', $brand)) . '" title="Ver" aria-label="Ver marca">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm brand-action-btn btnEditar" data-id="' . $brand->id . '" data-url="' . e(route('brands.edit', $brand)) . '" title="Editar" aria-label="Editar marca">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm brand-action-btn btnEliminar" data-id="' . $brand->id . '" data-url="' . e(route('brands.destroy', $brand)) . '" title="Eliminar" aria-label="Eliminar marca">
                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['logo', 'actions'])
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
        $zones = [
            '1'=> 'Zona A',
            '2'=> 'Zona B'
        ];
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
    public function store(Request $request)
    {
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
