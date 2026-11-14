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
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:user_types,name'], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['sometimes', 'boolean']]);
        $slug = Str::slug($data['name']);
        if (UserType::where('slug', $slug)->exists()) return back()->withErrors(['name' => 'Ya existe un tipo de usuario con un nombre similar.'])->withInput();
        UserType::create($data + ['slug' => $slug, 'is_active' => $request->boolean('is_active', true)]);
        return back()->with('success', 'El tipo de usuario se creó correctamente.');
    }

    public function update(Request $request, UserType $userType)
    {
        abort_if($userType->is_system, 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('user_types', 'name')->ignore($userType->id)], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean']]);
        $slug = Str::slug($data['name']);
        if (UserType::where('slug', $slug)->where('id', '!=', $userType->id)->exists()) return back()->withErrors(['name' => 'Ya existe un tipo de usuario con un nombre similar.'])->withInput();
        $userType->update($data + ['slug' => $slug]);
        return back()->with('success', 'El tipo de usuario se actualizó correctamente.');
    }

    public function destroy(UserType $userType)
    {
        abort_if($userType->is_system || $userType->users()->exists(), 422, 'No se puede eliminar un tipo protegido o asignado a usuarios.');
        $userType->delete();
        return back()->with('success', 'El tipo de usuario se eliminó correctamente.');
    }

    public function permissions(UserType $userType)
    {
        $this->ensurePermissionsConfigurable($userType);
        return view('admin.user-types.permissions', ['type' => $userType, 'modules' => Module::with(['children.permissions', 'permissions'])->whereNull('parent_id')->orderBy('sort_order')->get(), 'assigned' => $userType->permissions()->pluck('permissions.id')->all()]);
    }

    public function savePermissions(Request $request, UserType $userType)
    {
        $this->ensurePermissionsConfigurable($userType);
        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['integer', 'exists:permissions,id']]);
        DB::transaction(fn () => $userType->permissions()->sync($data['permissions'] ?? []));
        return redirect()->route('user-types.index')->with('success', 'Los permisos del tipo de usuario se actualizaron correctamente.');
    }

    private function ensurePermissionsConfigurable(UserType $userType): void
    {
        abort_if($userType->is_admin || in_array($userType->slug, ['administrador', 'pendiente-de-asignacion'], true), 403);
    }
}
