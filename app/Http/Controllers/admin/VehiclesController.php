<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VehiclesController extends Controller
{
    /** Display the vehicle inventory. */
    public function index(Request $request)
    {
        $vehicles = Vehicle::query()
            ->leftJoin('brands', 'vehicles.brand_id', '=', 'brands.id')
            ->leftJoin('brandmodels', 'vehicles.model_id', '=', 'brandmodels.id')
            ->leftJoin('color', 'vehicles.color_id', '=', 'color.id')
            ->leftJoin('vehicletypes', 'vehicles.type_id', '=', 'vehicletypes.id')
            ->select([
                'vehicles.id',
                'vehicles.code',
                'vehicles.name',
                'vehicles.plate',
                'vehicles.created_at',
                'vehicles.updated_at',
                'brands.name as brand',
                'brandmodels.name as model',
                'color.name as color',
                'vehicletypes.name as type',
            ]);

        if ($request->ajax()) {
            return DataTables::of($vehicles)->make(true);
        }

        return view('admin.vehicles.index');
    }
}
