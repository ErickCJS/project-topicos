<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) return view('admin.user.index');
        $query = User::query();
        $total = (clone $query)->count();
        $search = $request->input('search.value');
        if ($search) $query->where(fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%")->orWhere('dni', 'like', "%$search%")->orWhere('email', 'like', "%$search%")->orWhere('phone', 'like', "%$search%")->orWhere('user_type_label', 'like', "%$search%"));
        $filtered = (clone $query)->count();
        $columns = ['name', 'last_name', 'dni', 'email', 'phone', 'user_type_label', 'is_active', 'updated_at'];
        $order = $request->input('order.0.column');
        if (isset($columns[$order])) $query->orderBy($columns[$order], $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc');
        $users = $query->skip(max(0, (int) $request->input('start', 0)))->take(min(100, max(1, (int) $request->input('length', 10))))->get();
        $data = $users->map(fn ($user) => [
            'photo' => '<img class="user-avatar" src="'.e($user->profile_photo_url).'" alt="Fotografía de perfil">',
            'name' => e(trim($user->name.' '.$user->last_name)), 'dni' => e($user->dni ?: '—'), 'email' => e($user->email),
            'phone' => e($user->phone ?: '—'), 'type' => $user->is_admin ? 'Administrador' : e($user->user_type_label ?: '—'),
            'status' => $user->is_active ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>',
            'updated_at' => $user->updated_at?->format('d/m/Y H:i') ?: '—', 'actions' => view('admin.user.partials.actions', compact('user'))->render(),
        ]);
        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
    }

    public function create(Request $request) { return $request->header('X-Modal-Request') ? view('admin.user.partials.form-modal') : view('admin.user.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:150'], 'dni' => ['required', 'digits:8', 'unique:users,dni'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8'],
            'is_admin' => ['sometimes', 'boolean'], 'birthdate' => ['nullable', 'date', 'before_or_equal:today'], 'phone' => ['nullable', 'string', 'max:30'], 'license' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'], 'profile_photo' => ['nullable', 'image', 'max:5120'], 'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_admin'] = $request->boolean('is_admin');
        $data['is_active'] = $request->boolean('is_active', true);
        if ($request->hasFile('profile_photo')) $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        unset($data['profile_photo']);
        User::create($data);
        return $request->ajax() ? response()->json(['message' => 'El usuario se registró correctamente.']) : redirect()->route('users.index')->with('success', 'El usuario se registró correctamente.');
    }

    public function show(Request $request, User $user) { $user->setAttribute('password', null); return $request->header('X-Modal-Request') ? view('admin.user.partials.show-modal', compact('user')) : view('admin.user.show', compact('user')); }

    public function edit(Request $request, User $user)
    {
        $user->setAttribute('password', null);
        return $request->header('X-Modal-Request') ? view('admin.user.partials.form-modal', compact('user')) : view('admin.user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:150'], 'dni' => ['required', 'digits:8', Rule::unique('users', 'dni')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], 'is_admin' => ['sometimes', 'boolean'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'], 'phone' => ['nullable', 'string', 'max:30'], 'license' => ['nullable', 'string', 'max:50'], 'address' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'max:5120'], 'is_active' => ['required', 'boolean'],
            'change_password' => ['sometimes', 'in:1'], 'password' => ['exclude_unless:change_password,1', 'required', 'string', 'min:8'],
        ]);
        if (!$request->boolean('change_password')) unset($data['password']);
        unset($data['change_password']);
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) Storage::disk('public')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }
        unset($data['profile_photo']);
        $updated = DB::transaction(function () use ($user, $data, $request) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $becomesNonAdmin = $lockedUser->is_admin && (!($data['is_admin'] ?? true) || !$request->boolean('is_active'));
            if ($becomesNonAdmin && User::where('is_admin', true)->where('is_active', true)->lockForUpdate()->count() <= 1) return false;
            $lockedUser->update($data);
            return true;
        });
        if (!$updated) return $this->validationError($request, 'is_admin', 'No se puede quitar privilegios o desactivar al último administrador activo.');
        return $request->ajax() ? response()->json(['message' => 'Los datos del usuario se actualizaron correctamente.']) : redirect()->route('users.index')->with('success', 'Los datos del usuario se actualizaron correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) return response()->json(['message' => 'No se puede eliminar la propia cuenta.'], 422);
        if ($user->is_admin) return response()->json(['message' => 'No se puede eliminar una cuenta Administrador.'], 422);
        if ($user->profile_photo_path) Storage::disk('public')->delete($user->profile_photo_path);
        $user->delete();
        return $request->ajax() ? response()->json(['message' => 'El usuario se eliminó correctamente.']) : redirect()->route('users.index')->with('success', 'El usuario se eliminó correctamente.');
    }

    public function status(Request $request, User $user)
    {
        $active = $request->boolean('is_active');
        $updated = DB::transaction(function () use ($user, $active) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (!$active && $lockedUser->is_admin && $lockedUser->is_active && User::where('is_admin', true)->where('is_active', true)->lockForUpdate()->count() <= 1) return false;
            $lockedUser->update(['is_active' => $active]);
            return true;
        });
        if (!$updated) return response()->json(['message' => 'No se puede desactivar al último administrador activo.'], 422);
        return response()->json(['message' => 'El estado del usuario se actualizó correctamente.']);
    }

    public function updateTypeLabel(Request $request, User $user)
    {
        $data = $request->validate(['user_type_label' => ['nullable', 'string', 'max:100']]);
        $user->update(['user_type_label' => $data['user_type_label'] ?? null]);
        return response()->json(['message' => 'La clasificación se actualizó correctamente.']);
    }

    public function permissions(Request $request, User $user)
    {
        $data = ['user' => $user, 'modules' => Module::with(['children.permissions', 'permissions'])->whereNull('parent_id')->orderBy('sort_order')->get(), 'assigned' => $user->permissions()->pluck('permissions.id')->all()];
        return view('admin.user.partials.permissions-modal', $data);
    }

    public function savePermissions(Request $request, User $user)
    {
        if ($user->is_admin) return response()->json(['message' => 'Los administradores conservan acceso total y no requieren permisos individuales.'], 422);
        $data = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['integer', 'exists:permissions,id']]);
        DB::transaction(fn () => $user->permissions()->sync($data['permissions'] ?? []));
        return response()->json(['message' => 'Los permisos individuales se actualizaron correctamente.']);
    }

    private function validationError(Request $request, string $field, string $message)
    {
        return $request->ajax() ? response()->json(['message' => $message, 'errors' => [$field => [$message]]], 422) : back()->withErrors([$field => $message])->withInput();
    }
}
