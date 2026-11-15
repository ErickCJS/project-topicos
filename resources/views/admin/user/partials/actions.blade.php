<div class="d-flex justify-content-center gap-1">
    <button class="btn btn-sm btn-outline-primary" type="button" data-user-modal="{{ route('users.show', $user) }}" aria-label="Ver usuario"><i class="bi bi-eye"></i></button>
    <button class="btn btn-sm btn-outline-warning" type="button" data-user-modal="{{ route('users.edit', $user) }}" aria-label="Editar usuario"><i class="bi bi-pencil"></i></button>
    <button class="btn btn-sm btn-outline-secondary" type="button" data-classification-label="{{ route('users.type-label', $user) }}" data-current-label="{{ $user->user_type_label }}" aria-label="Cambiar clasificación"><i class="bi bi-tag"></i></button>
    @if(!$user->is_admin && auth()->id() !== $user->id)
    <button class="btn btn-sm btn-outline-info" type="button" data-user-modal="{{ route('users.permissions', $user) }}" aria-label="Configurar permisos"><i class="bi bi-key"></i></button>
    <button class="btn btn-sm btn-outline-danger" type="button" data-user-delete="{{ route('users.destroy', $user) }}" aria-label="Eliminar usuario"><i class="bi bi-trash"></i></button>
    @endif
</div>
