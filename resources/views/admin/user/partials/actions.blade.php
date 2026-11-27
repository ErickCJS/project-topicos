<div class="d-flex justify-content-center gap-1">
    <button
        class="btn btn-sm btn-outline-primary"
        type="button"
        data-user-modal="{{ route('users.show', $user) }}"
        aria-label="Ver usuario"
        title="Ver usuario">
        <i class="bi bi-eye"></i>
    </button>

    <button
        class="btn btn-sm btn-outline-warning"
        type="button"
        data-user-modal="{{ route('users.edit', $user) }}"
        aria-label="Editar usuario"
        title="Editar usuario">
        <i class="bi bi-pencil"></i>
    </button>

    @if(!$user->is_admin && auth()->id() !== $user->id)
        <button
            class="btn btn-sm btn-outline-info"
            type="button"
            data-user-modal="{{ route('users.permissions', $user) }}"
            aria-label="Configurar permisos"
            title="Configurar permisos">
            <i class="bi bi-key"></i>
        </button>

        <button
            class="btn btn-sm btn-outline-danger"
            type="button"
            data-user-delete="{{ route('users.destroy', $user) }}"
            aria-label="Eliminar usuario"
            title="Eliminar usuario">
            <i class="bi bi-trash"></i>
        </button>
    @endif
</div>
