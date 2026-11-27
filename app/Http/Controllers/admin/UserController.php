<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax()) {
            return view('admin.user.index');
        }

        $query = User::query()->with('userType');

        $total = User::count();
        $search = trim((string) $request->input('search.value', ''));

        if ($search !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('dni', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('userType', function (Builder $typeQuery) use ($search) {
                        $typeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $filtered = (clone $query)->count();

        $columns = [
            1 => 'users.name',
            2 => 'users.dni',
            3 => 'users.email',
            4 => 'users.phone',
            5 => 'user_types.name',
            6 => 'users.is_active',
            7 => 'users.updated_at',
        ];

        $order = (int) $request->input('order.0.column', 7);
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';

        if (isset($columns[$order])) {
            if ($order === 5) {
                $query->leftJoin('user_types', 'users.user_type_id', '=', 'user_types.id')
                    ->select('users.*')
                    ->orderBy($columns[$order], $direction);
            } else {
                $query->orderBy($columns[$order], $direction);
            }
        }

        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));

        $users = $query
            ->skip($start)
            ->take($length)
            ->get();

        $data = $users->map(function ($user) {
            $type = e($user->userType?->name ?: '—');

            $adminBadge = $user->is_admin
                ? '<span class="badge text-bg-dark ms-1">Acceso total</span>'
                : '';

            return [
                'photo' => '<img class="user-avatar" src="' . e($user->profile_photo_url) . '" alt="Fotografía de perfil">',
                'name' => e(trim($user->name . ' ' . $user->last_name)),
                'dni' => e($user->dni ?: '—'),
                'email' => e($user->email),
                'phone' => e($user->phone ?: '—'),
                'type' => '<span>' . $type . '</span> ' . $adminBadge,
                'status' => $user->is_active
                    ? '<span class="badge text-bg-success">Activo</span>'
                    : '<span class="badge text-bg-secondary">Inactivo</span>',
                'updated_at' => $user->updated_at?->format('d/m/Y H:i') ?: '—',
                'actions' => view('admin.user.partials.actions', compact('user'))->render(),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    public function create(Request $request)
    {
        $types = UserType::orderBy('name')->get();

        if ($request->header('X-Modal-Request')) {
            return view('admin.user.partials.form-modal', compact('types'));
        }

        return view('admin.user.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:150'],
            'dni' => ['required', 'digits:8', 'unique:users,dni'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'user_type_id' => ['nullable', 'integer', 'exists:user_types,id'],
            'is_admin' => ['sometimes', 'boolean'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_admin'] = $request->user()->is_admin
            ? $request->boolean('is_admin')
            : false;

        $data['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')
                ->store('profile-photos', 'public');
        }

        unset($data['profile_photo']);

        DB::transaction(function () use ($data) {
            $user = User::create($data);

            // El tipo funciona como plantilla inicial: copia sus permisos al crear la cuenta.
            if ($user->user_type_id) {
                $permissionIds = UserType::find($user->user_type_id)
                    ?->permissions()
                    ->pluck('permissions.id')
                    ->all() ?? [];

                $user->permissions()->sync($permissionIds);
            }
        });

        return $this->wantsJsonResponse($request)
            ? response()->json([
                'message' => 'El usuario se registró correctamente.',
            ], 201)
            : redirect()->route('users.index')
                ->with('success', 'El usuario se registró correctamente.');
    }

    public function show(Request $request, User $user)
    {
        $user->load('userType');
        $user->setAttribute('password', null);

        if ($request->header('X-Modal-Request')) {
            return view('admin.user.partials.show-modal', compact('user'));
        }

        return view('admin.user.show', compact('user'));
    }

    public function edit(Request $request, User $user)
    {
        $user->load('userType');
        $user->setAttribute('password', null);

        $types = UserType::orderBy('name')->get();

        if ($request->header('X-Modal-Request')) {
            return view('admin.user.partials.form-modal', compact('user', 'types'));
        }

        return view('admin.user.edit', compact('user', 'types'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:150'],
            'dni' => [
                'required',
                'digits:8',
                Rule::unique('users', 'dni')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'user_type_id' => ['nullable', 'integer', 'exists:user_types,id'],
            'is_admin' => ['sometimes', 'boolean'],
            'birthdate' => ['nullable', 'date', 'before_or_equal:today'],
            'phone' => ['nullable', 'string', 'max:30'],
            'license' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'profile_photo' => ['nullable', 'image', 'max:5120'],
            'is_active' => ['required', 'boolean'],
            'change_password' => ['sometimes', 'in:1'],
            'password' => [
                'exclude_unless:change_password,1',
                'required',
                'string',
                'min:8',
            ],
        ]);

        if (!$request->user()->is_admin) {
            unset($data['is_admin']);
        } else {
            $data['is_admin'] = $request->boolean('is_admin');
        }

        if (!$request->boolean('change_password')) {
            unset($data['password']);
        }

        unset($data['change_password']);

        $previousPhotoPath = $user->profile_photo_path;
        $newPhotoPath = null;

        if ($request->hasFile('profile_photo')) {
            $newPhotoPath = $request->file('profile_photo')
                ->store('profile-photos', 'public');

            if (!$newPhotoPath) {
                return $this->validationError(
                    $request,
                    'profile_photo',
                    'No se pudo guardar la fotografía. Inténtelo nuevamente.'
                );
            }

            $data['profile_photo_path'] = $newPhotoPath;
        }

        unset($data['profile_photo']);

        try {
            $updated = DB::transaction(function () use ($user, $data) {
                $lockedUser = User::whereKey($user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $becomesNonAdmin = $lockedUser->is_admin && (
                    (array_key_exists('is_admin', $data) && !$data['is_admin'])
                    || (array_key_exists('is_active', $data) && !$data['is_active'])
                );

                if (
                    $becomesNonAdmin
                    && User::where('is_admin', true)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->count() <= 1
                ) {
                    return false;
                }

                $previousTypeId = $lockedUser->user_type_id;
                $newTypeId = array_key_exists('user_type_id', $data)
                    ? ($data['user_type_id'] !== null ? (int) $data['user_type_id'] : null)
                    : $previousTypeId;

                $lockedUser->update($data);

                // Al cambiar de tipo se aplica la nueva plantilla.
                // Si el tipo no cambia, se conservan los permisos personalizados del usuario.
                if ($previousTypeId !== $newTypeId) {
                    $permissionIds = $newTypeId
                        ? (UserType::find($newTypeId)?->permissions()
                            ->pluck('permissions.id')
                            ->all() ?? [])
                        : [];

                    $lockedUser->permissions()->sync($permissionIds);
                }

                return true;
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if (!$updated) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            return $this->validationError(
                $request,
                'is_admin',
                'No se puede quitar privilegios o desactivar al último administrador activo.'
            );
        }

        if ($newPhotoPath && $previousPhotoPath) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

        return $this->wantsJsonResponse($request)
            ? response()->json([
                'message' => 'Los datos del usuario se actualizaron correctamente.',
            ])
            : redirect()->route('users.index')
                ->with('success', 'Los datos del usuario se actualizaron correctamente.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($request->user()->is($user)) {
            return response()->json([
                'message' => 'No se puede eliminar la propia cuenta.',
            ], 422);
        }

        if ($user->is_admin) {
            return response()->json([
                'message' => 'No se puede eliminar una cuenta con acceso administrativo total.',
            ], 422);
        }

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->delete();

        return $this->wantsJsonResponse($request)
            ? response()->json([
                'message' => 'El usuario se eliminó correctamente.',
            ])
            : redirect()->route('users.index')
                ->with('success', 'El usuario se eliminó correctamente.');
    }

    public function status(Request $request, User $user)
    {
        $active = $request->boolean('is_active');

        $updated = DB::transaction(function () use ($user, $active) {
            $lockedUser = User::whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                !$active
                && $lockedUser->is_admin
                && $lockedUser->is_active
                && User::where('is_admin', true)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->count() <= 1
            ) {
                return false;
            }

            $lockedUser->update([
                'is_active' => $active,
            ]);

            return true;
        });

        if (!$updated) {
            return response()->json([
                'message' => 'No se puede desactivar al último administrador activo.',
            ], 422);
        }

        return response()->json([
            'message' => 'El estado del usuario se actualizó correctamente.',
        ]);
    }

    public function permissions(Request $request, User $user)
    {
        $data = [
            'user' => $user,
            'modules' => Module::with([
                'children.permissions',
                'permissions',
            ])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->get(),
            'assigned' => $user->permissions()
                ->pluck('permissions.id')
                ->all(),
        ];

        return view('admin.user.partials.permissions-modal', $data);
    }

    public function savePermissions(Request $request, User $user)
    {
        if ($user->is_admin) {
            return response()->json([
                'message' => 'Los administradores conservan acceso total y no requieren permisos individuales.',
            ], 422);
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->permissions()->sync($data['permissions'] ?? []);
        });

        return response()->json([
            'message' => 'Los permisos individuales se actualizaron correctamente.',
        ]);
    }

    private function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function validationError(
        Request $request,
        string $field,
        string $message
    ) {
        return $this->wantsJsonResponse($request)
            ? response()->json([
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
            ], 422)
            : back()->withErrors([
                $field => $message,
            ])->withInput();
    }
}
