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
        $this->ensureIdentityEditable($userType);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('user_types', 'name')->ignore($userType->id)], 'description' => ['nullable', 'string', 'max:1000'], 'is_active' => ['required', 'boolean']]);
        DB::transaction(fn () => $userType->update($data));
        return back()->with('success', 'El tipo de usuario se actualizó correctamente.');
    }

    public function destroy(UserType $userType)
    {
        abort_if($userType->is_admin || $userType->slug === 'administrador', 422, 'El tipo Administrador está protegido.');
        $deleted = DB::transaction(function () use ($userType) {
            $lockedType = UserType::whereKey($userType->id)->lockForUpdate()->firstOrFail();
            if ($lockedType->users()->lockForUpdate()->exists()) return false;
            $lockedType->delete();
            return true;
        });
        if (!$deleted) return back()->withErrors(['user_type' => 'Reasigna primero a los usuarios de este tipo.']);
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('user_types', 'name')->ignore($userType->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
        DB::transaction(function () use ($userType, $data) {
            $userType->update(['name' => $data['name'], 'description' => $data['description'] ?? null]);
            $userType->permissions()->sync($data['permissions'] ?? []);
        });
        return redirect()->route('user-types.index')->with('success', 'Los permisos del tipo de usuario se actualizaron correctamente.');
    }

    private function ensurePermissionsConfigurable(UserType $userType): void
    {
        abort_if($userType->is_admin || in_array($userType->slug, ['administrador', 'pendiente-de-asignacion'], true), 403);
    }

    private function ensureIdentityEditable(UserType $userType): void
    {
        abort_if($userType->is_admin || in_array($userType->slug, ['administrador', 'pendiente-de-asignacion'], true), 403);
    }
}
