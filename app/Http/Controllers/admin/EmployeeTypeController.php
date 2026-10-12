<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\EmployeeType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class EmployeeTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $types = EmployeeType::query();

            return DataTables::of($types)
                ->editColumn('created_at', function ($type) {
                    return $type->created_at?->format('d/m/Y H:i:s');
                })
                ->editColumn('updated_at', function ($type) {
                    return $type->updated_at?->format('d/m/Y H:i:s');
                })
                ->addColumn('actions', function ($type) {
                    return '<div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm btnEditar"
                            data-id="' . $type->id . '" title="Editar">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btnEliminar"
                            data-id="' . $type->id . '" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.employee_types.index');
    }

    public function create()
    {
        return view('admin.employee_types.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50|unique:employee_types,name',
            'description' => 'nullable|string|max:255',
        ]);

        EmployeeType::create($data);

        return response()->json([
            'message' => 'Tipo de personal registrado correctamente'
        ]);
    }

    public function edit(string $id)
    {
        $type = EmployeeType::findOrFail($id);

        return view('admin.employee_types.edit', compact('type'));
    }

    public function update(Request $request, string $id)
    {
        $type = EmployeeType::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:50|unique:employee_types,name,' . $id,
            'description' => 'nullable|string|max:255',
        ]);

        $type->update($data);

        return response()->json([
            'message' => 'Tipo de personal actualizado correctamente'
        ]);
    }

    public function destroy(string $id)
    {
        $type = EmployeeType::findOrFail($id);

        if (DB::table('employees')->where('employee_type_id', $id)->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar porque tiene personal relacionado'
            ], 409);
        }

        $type->delete();

        return response()->json([
            'message' => 'Tipo de personal eliminado correctamente'
        ]);
    }
}