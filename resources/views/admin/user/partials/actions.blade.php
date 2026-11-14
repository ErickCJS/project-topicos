<div class="d-flex justify-content-center gap-1">
    <a class="btn btn-sm btn-outline-primary" href="{{ route('users.show', $user) }}" aria-label="Ver usuario"><i class="bi bi-eye"></i></a>
    <a class="btn btn-sm btn-outline-warning" href="{{ route('users.edit', $user) }}" aria-label="Editar usuario"><i class="bi bi-pencil"></i></a>
    @if(!$user->userType?->is_admin || \App\Models\User::where('is_active', true)->where('user_type_id', $user->user_type_id)->count() > 1)
    <form method="post" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('¿Desea eliminar este usuario?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Eliminar usuario"><i class="bi bi-trash"></i></button></form>
    @endif
</div>
