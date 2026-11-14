<div class="d-flex justify-content-center gap-1">
    <button class="btn btn-sm btn-outline-primary" type="button" data-user-modal="{{ route('users.show', $user) }}" aria-label="Ver usuario"><i class="bi bi-eye"></i></button>
    <button class="btn btn-sm btn-outline-warning" type="button" data-user-modal="{{ route('users.edit', $user) }}" aria-label="Editar usuario"><i class="bi bi-pencil"></i></button>
    @if(!$user->userType?->is_admin || \App\Models\User::where('is_active', true)->where('user_type_id', $user->user_type_id)->count() > 1)
    <button class="btn btn-sm btn-outline-danger" type="button" data-user-delete="{{ route('users.destroy', $user) }}" aria-label="Eliminar usuario"><i class="bi bi-trash"></i></button>
    @endif
</div>
