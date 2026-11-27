<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class UserTypeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() && ! $request->header('X-Modal-Request')) {
            $query = UserType::query()->withCount('permissions');

            return DataTables::eloquent($query)
                ->editColumn('created_at', fn (UserType $type) => $type->created_at?->format('d/m/Y H:i:s'))
                ->editColumn('updated_at', fn (UserType $type) => $type->updated_at?->format('d/m/Y H:i:s'))
                ->addColumn('actions', function (UserType $type) {
                    return '<div class="user-type-actions">
                        <button
                            type="button"
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

        return view('admin.user-types.partials.index-modal');
    }

    public function create()
    {
        return view('admin.user-types.partials.form-modal', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $permissionIds = $data['permissions'] ?? [];
        unset($data['permissions']);

        $type = DB::transaction(function () use ($data, $permissionIds) {
            $type = UserType::create($data);
            $type->permissions()->sync($permissionIds);
            $type->forceFill(['permissions_configured' => true])->save();

            return $type;
        });

        return response()->json([
            'message' => 'Tipo de usuario registrado correctamente.',
            'id' => $type->id,
        ]);
    }

    public function edit(string $id)
    {
        $type = UserType::with('permissions')->findOrFail($id);

        return view(
            'admin.user-types.partials.form-modal',
            $this->formData($type) + compact('type')
        );
    }

    public function update(Request $request, string $id)
    {
        $type = UserType::findOrFail($id);
        $data = $this->validatedData($request, $type);
        $permissionIds = $data['permissions'] ?? [];
        unset($data['permissions']);

        DB::transaction(function () use ($type, $data, $permissionIds) {
            $type->update($data);
            $type->permissions()->sync($permissionIds);
            $type->forceFill(['permissions_configured' => true])->save();
        });

        return response()->json([
            'message' => 'Tipo de usuario actualizado correctamente.',
        ]);
    }

    private function formData(?UserType $type = null): array
    {
        $modules = Module::query()
            ->with([
                'permissions' => fn ($query) => $query->orderBy('name'),
                'children.permissions',
            ])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'modules' => $modules,
            'assigned' => $type
                ? $type->permissions()->pluck('permissions.id')->all()
                : [],
            'availableCount' => Permission::count(),
        ];
    }

    private function validatedData(Request $request, ?UserType $type = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('user_types', 'name')->ignore($type?->id),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'integer',
                'distinct',
                'exists:permissions,id',
            ],
        ], [
            'name.required' => 'El nombre del tipo de usuario es obligatorio.',
            'name.string' => 'El nombre del tipo de usuario debe ser texto.',
            'name.max' => 'El nombre no debe superar los 100 caracteres.',
            'name.unique' => 'Ya existe un tipo de usuario con ese nombre.',
            'description.string' => 'La descripción debe ser texto.',
            'description.max' => 'La descripción no debe superar los 255 caracteres.',
            'permissions.array' => 'La selección de permisos no tiene un formato válido.',
            'permissions.*.integer' => 'Uno de los permisos seleccionados no es válido.',
            'permissions.*.distinct' => 'No se puede repetir un permiso.',
            'permissions.*.exists' => 'Uno de los permisos seleccionados ya no existe.',
        ]);
    }
}
