<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserTypeController extends Controller
{
    public function index() { return view('admin.user-types.index', ['types' => UserType::withCount('users')->orderBy('name')->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:user_types,name'], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['sometimes', 'boolean']], ['required' => 'El campo :attribute es obligatorio.', 'unique' => 'Ya existe un registro con este :attribute.', 'max' => 'El :attribute no debe superar :max caracteres.'], ['name' => 'nombre', 'description' => 'descripción']);
        $slug = Str::slug($data['name']);
        if (UserType::where('slug', $slug)->exists()) return $request->ajax() ? response()->json(['message' => 'Ya existe un tipo de usuario con un nombre similar.', 'errors' => ['name' => ['Ya existe un tipo de usuario con un nombre similar.']]], 422) : back()->withErrors(['name' => 'Ya existe un tipo de usuario con un nombre similar.'])->withInput();
        UserType::create($data + ['slug' => $slug, 'is_active' => $request->boolean('is_active', true)]);
        return $request->ajax() ? response()->json(['message' => 'El tipo de usuario se creó correctamente.']) : back()->with('success', 'El tipo de usuario se creó correctamente.');
    }

    public function update(Request $request, UserType $userType)
    {
        $this->ensureIdentityEditable($userType);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('user_types', 'name')->ignore($userType->id)], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean']], ['required' => 'El campo :attribute es obligatorio.', 'unique' => 'Ya existe un registro con este :attribute.', 'max' => 'El :attribute no debe superar :max caracteres.', 'boolean' => 'Seleccione un estado válido.'], ['name' => 'nombre', 'description' => 'descripción', 'is_active' => 'estado']);
        DB::transaction(fn () => $userType->update($data));
        return $request->ajax() ? response()->json(['message' => 'El tipo de usuario se actualizó correctamente.']) : back()->with('success', 'El tipo de usuario se actualizó correctamente.');
    }

    public function destroy(Request $request, UserType $userType)
    {
        if ($userType->is_admin || $userType->slug === 'administrador') return $request->ajax() ? response()->json(['message' => 'El tipo Administrador está protegido.'], 422) : response('El tipo Administrador está protegido.', 422);
        $deleted = DB::transaction(function () use ($userType) {
            $lockedType = UserType::whereKey($userType->id)->lockForUpdate()->firstOrFail();
            if ($lockedType->users()->lockForUpdate()->exists()) return false;
            $lockedType->delete();
            return true;
        });
        if (!$deleted) return $request->ajax() ? response()->json(['message' => 'Reasigna primero a los usuarios de este tipo.'], 422) : back()->withErrors(['user_type' => 'Reasigna primero a los usuarios de este tipo.']);
        return $request->ajax() ? response()->json(['message' => 'El tipo de usuario se eliminó correctamente.']) : back()->with('success', 'El tipo de usuario se eliminó correctamente.');
    }

    public function permissions(UserType $userType)
    {
        $this->ensurePermissionsConfigurable($userType);
        $data = ['type' => $userType, 'modules' => Module::with(['children.permissions', 'permissions'])->whereNull('parent_id')->orderBy('sort_order')->get(), 'assigned' => $userType->permissions()->pluck('permissions.id')->all()];
        return request()->header('X-Modal-Request') ? view('admin.user-types.partials.permissions-modal', $data) : view('admin.user-types.permissions', $data);
    }

    public function savePermissions(Request $request, UserType $userType)
    {
        $this->ensurePermissionsConfigurable($userType);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('user_types', 'name')->ignore($userType->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], ['required' => 'El campo :attribute es obligatorio.', 'unique' => 'Ya existe un registro con este :attribute.', 'max' => 'El :attribute no debe superar :max caracteres.', 'exists' => 'Uno de los permisos seleccionados ya no está disponible.'], ['name' => 'nombre', 'description' => 'descripción', 'permissions' => 'permisos']);
        DB::transaction(function () use ($userType, $data) {
            $userType->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);
            $userType->permissions()->sync($data['permissions'] ?? []);
        });
        return $request->ajax() ? response()->json(['message' => 'Los permisos del tipo de usuario se actualizaron correctamente.']) : redirect()->route('user-types.index')->with('success', 'Los permisos del tipo de usuario se actualizaron correctamente.');
    }

    private function ensurePermissionsConfigurable(UserType $userType): void
    {
        if ($userType->is_admin || in_array($userType->slug, ['administrador', 'pendiente-de-asignacion'], true)) {
            if (request()->expectsJson() || request()->header('X-Modal-Request')) abort(response()->json(['message' => 'No tiene permisos para modificar este tipo de usuario.'], 403));
            abort(403, 'No tiene permisos para modificar este tipo de usuario.');
        }
    }

    private function ensureIdentityEditable(UserType $userType): void
    {
        if ($userType->is_admin || in_array($userType->slug, ['administrador', 'pendiente-de-asignacion'], true)) {
            if (request()->expectsJson() || request()->header('X-Modal-Request')) abort(response()->json(['message' => 'No tiene permisos para modificar este tipo de usuario.'], 403));
            abort(403, 'No tiene permisos para modificar este tipo de usuario.');
        }
    }
}
