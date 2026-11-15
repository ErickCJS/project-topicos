<div class="users-module user-detail-modal">
    <div class="modal-header users-modal-header"><div class="users-modal-heading"><span class="users-modal-icon"><i class="bi bi-person-vcard"></i></span><h2 class="modal-title fs-5 mb-0">Detalle de usuario</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body">
        <section class="user-profile-summary card mb-4"><div class="card-body d-flex flex-column flex-sm-row align-items-center align-items-sm-center gap-3 text-center text-sm-start"><img class="user-avatar user-avatar-large" src="{{ $user->profile_photo_url }}" alt="Fotografía de {{ $user->name }}"><div class="min-w-0"><h3 class="h5 mb-2">{{ trim($user->name.' '.$user->last_name) }}</h3><div class="d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-2"><span class="badge bg-body-tertiary text-body border">{{ $user->is_admin ? 'Administrador' : ($user->user_type_label ?: '—') }}</span><span class="badge {{ $user->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></div></div></div></section>
        <div class="row g-3">
            @foreach(['DNI' => $user->dni, 'Correo electrónico' => $user->email, 'Teléfono' => $user->phone, 'Fecha de nacimiento' => $user->birthdate?->format('d/m/Y'), 'Licencia' => $user->license, 'Dirección' => $user->address] as $label => $value)
                <div class="col-12 col-md-6"><div class="user-detail-card h-100"><small>{{ $label }}</small><strong>{{ filled($value) ? $value : 'No registrado' }}</strong></div></div>
            @endforeach
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cerrar</button></div>
</div>
