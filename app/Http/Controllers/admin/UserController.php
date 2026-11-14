<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) return view('admin.user.index');
        $query = User::with('userType')->select('users.*');
        $total = (clone $query)->count();
        $search = $request->input('search.value');
        if ($search) $query->where(fn ($q) => $q->where('name', 'like', "%$search%")->orWhere('last_name', 'like', "%$search%")->orWhere('dni', 'like', "%$search%")->orWhere('email', 'like', "%$search%")->orWhere('phone', 'like', "%$search%")->orWhereHas('userType', fn ($type) => $type->where('name', 'like', "%$search%")));
        $filtered = (clone $query)->count();
        $columns = ['name', 'last_name', 'dni', 'email', 'phone', 'user_type_id', 'is_active', 'updated_at'];
        $order = $request->input('order.0.column');
        if (isset($columns[$order])) $query->orderBy($columns[$order], $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc');
        $users = $query->skip(max(0, (int) $request->input('start', 0)))->take(min(100, max(1, (int) $request->input('length', 10))))->get();
        $data = $users->map(fn ($user) => [
            'photo' => '<img class="user-avatar" src="'.e($user->profile_photo_url).'" alt="Fotografía de perfil">',
            'name' => e(trim($user->name.' '.$user->last_name)), 'dni' => e($user->dni ?: '—'), 'email' => e($user->email),
            'phone' => e($user->phone ?: '—'), 'type' => e($user->userType?->name ?: 'Pendiente de asignación'),
            'status' => $user->is_active ? '<span class="badge text-bg-success">Activo</span>' : '<span class="badge text-bg-secondary">Inactivo</span>',
            'updated_at' => $user->updated_at?->format('d/m/Y H:i') ?: '—', 'actions' => view('admin.user.partials.actions', compact('user'))->render(),
        ]);
        return response()->json(['draw' => (int) $request->input('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
    }

    public function create() { return view('admin.user.create', ['types' => UserType::where('is_active', true)->orderBy('name')->get()]); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:150'], 'dni' => ['required', 'digits:8', 'unique:users,dni'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'], 'password' => ['required', 'string', 'min:8'],
            'user_type_id' => ['required', 'exists:user_types,id'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'], 'phone' => ['nullable', 'string', 'max:30'], 'license' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'], 'profile_photo' => ['nullable', 'image', 'max:5120'], 'is_active' => ['sometimes', 'boolean'],
        ], [], ['name' => 'nombres', 'last_name' => 'apellidos', 'dni' => 'DNI', 'email' => 'correo electrónico', 'password' => 'contraseña', 'user_type_id' => 'tipo de usuario']);
        if (UserType::whereKey($data['user_type_id'])->where('is_active', true)->doesntExist()) return back()->withErrors(['user_type_id' => 'Seleccione un tipo de usuario activo.'])->withInput();
        $photo = $request->file('profile_photo')?->store('profile-photos', 'public');
        unset($data['profile_photo']);
        $data['profile_photo_path'] = $photo;
        $data['is_active'] = $request->boolean('is_active', true);
        User::create($data);
        return redirect()->route('users.index')->with('success', 'El usuario se registró correctamente.');
    }

    public function show(User $user) { return view('admin.user.show', compact('user')); }

    public function edit(User $user)
    {
        return view('admin.user.edit', ['user' => $user, 'types' => UserType::where('is_active', true)->orderBy('name')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $isAdmin = $user->userType?->is_admin;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'last_name' => ['required', 'string', 'max:150'], 'dni' => ['required', 'digits:8', Rule::unique('users', 'dni')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)], 'password' => ['nullable', 'string', 'min:8'],
            'user_type_id' => ['required', Rule::exists('user_types', 'id')->where('is_active', true)], 'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'], 'license' => ['nullable', 'string', 'max:50'], 'address' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'max:5120'], 'is_active' => ['required', 'boolean'],
        ], [], ['name' => 'nombres', 'last_name' => 'apellidos', 'user_type_id' => 'tipo de usuario']);
        if ($isAdmin) $data['user_type_id'] = $user->user_type_id;
        if (!$data['password']) unset($data['password']);
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) Storage::disk('public')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }
        unset($data['profile_photo']);
        if ($isAdmin && !$request->boolean('is_active') && User::where('is_active', true)->where('user_type_id', $user->user_type_id)->count() <= 1) return back()->withErrors(['is_active' => 'No se puede desactivar al último administrador activo.'])->withInput();
        $user->update($data);
        return redirect()->route('users.index')->with('success', 'Los datos del usuario se actualizaron correctamente.');
    }

    public function destroy(User $user)
    {
        if ($user->userType?->is_admin && $user->is_active && User::where('is_active', true)->where('user_type_id', $user->user_type_id)->count() <= 1) return response()->json(['mensaje' => 'No se puede eliminar al último administrador activo.'], 422);
        if ($user->profile_photo_path) Storage::disk('public')->delete($user->profile_photo_path);
        $user->delete();
        return response()->json(['mensaje' => 'El usuario se eliminó correctamente.']);
    }

    public function status(Request $request, User $user)
    {
        $active = $request->boolean('is_active');
        if (!$active && $user->userType?->is_admin && $user->is_active && User::where('is_active', true)->where('user_type_id', $user->user_type_id)->count() <= 1) return response()->json(['mensaje' => 'No se puede desactivar al último administrador activo.'], 422);
        $user->update(['is_active' => $active]);
        return response()->json(['mensaje' => 'El estado del usuario se actualizó correctamente.']);
    }

    public function assignType(Request $request, User $user)
    {
        $data = $request->validate(['user_type_id' => ['required', 'exists:user_types,id']]);
        $type = UserType::findOrFail($data['user_type_id']);
        if (!$type->is_active) return response()->json(['mensaje' => 'El tipo de usuario seleccionado está inactivo.'], 422);
        if ($user->userType?->is_admin && !$type->is_admin && User::where('is_active', true)->where('user_type_id', $user->user_type_id)->count() <= 1) return response()->json(['mensaje' => 'No se puede dejar el sistema sin un administrador activo.'], 422);
        $user->update(['user_type_id' => $type->id]);
        return response()->json(['mensaje' => 'El tipo de usuario se asignó correctamente.']);
    }
}
