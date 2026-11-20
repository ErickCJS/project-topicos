<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class UserTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() && !$request->header('X-Modal-Request')) {
            $types = UserType::query();

            return DataTables::of($types)
                ->editColumn('created_at', function ($type) {
                    return $type->created_at?->format('d/m/Y H:i:s');
                })
                ->editColumn('updated_at', function ($type) {
                    return $type->updated_at?->format('d/m/Y H:i:s');
                })
                ->addColumn('actions', function (UserType $type) {
                    return '<div class="user-type-actions">
                                <button type="button"
                                    class="btn btn-outline-warning btn-sm user-type-action-btn btnEditarTipo"
                                    data-id="'.$type->id.'"
                                    data-url="'.e(route('user-types.edit', $type->id)).'"
                                    title="Editar"
                                    aria-label="Editar tipo de usuario">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                </button>
                            </div>';
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        return view('admin.user_types.partials.index-modal');
    }

    public function create()
    {
        return view('admin.user_types.partials.form-modal');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:user_types,name',
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], $this->validationMessages());

        UserType::create($data);

        return response()->json([
            'message' => 'Tipo de usuario registrado correctamente.',
        ]);
    }

    public function edit(string $id)
    {
        $type = UserType::findOrFail($id);

        return view('admin.user_types.partials.form-modal', compact('type'));
    }

    public function update(Request $request, string $id)
    {
        $type = UserType::findOrFail($id);

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('user_types', 'name')->ignore($type->id),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], $this->validationMessages());

        $type->update($data);

        return response()->json([
            'message' => 'Tipo de usuario actualizado correctamente.',
        ]);
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'El nombre del tipo de usuario es obligatorio.',
            'name.string' => 'El nombre del tipo de usuario debe ser texto.',
            'name.max' => 'El nombre no debe superar los 100 caracteres.',
            'name.unique' => 'Ya existe un tipo de usuario con ese nombre.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe superar los 255 caracteres.',
        ];
    }
}
