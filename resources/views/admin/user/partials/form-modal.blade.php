<div class="modal-header users-modal-header">
    <div class="users-modal-heading">
        <span class="users-modal-icon" aria-hidden="true">
            <i class="bi {{ isset($user) ? 'bi-person-gear' : 'bi-person-plus' }}"></i>
        </span>

        <div>
            <h5 class="modal-title mb-0" id="usersModalTitle">
                {{ isset($user) ? 'Editar usuario' : 'Nuevo usuario' }}
            </h5>
            <small class="text-muted">
                {{ isset($user) ? 'Actualiza la información del usuario.' : 'Completa los datos para registrar un nuevo usuario.' }}
            </small>
        </div>
    </div>

    <button
        type="button"
        class="btn-close"
        data-bs-dismiss="modal"
        aria-label="Cerrar">
    </button>
</div>

<form
    method="POST"
    action="{{ isset($user) ? route('users.update', $user) : route('users.store') }}"
    enctype="multipart/form-data"
    class="js-modal-form users-modal-form">

    @include('admin.user.template.form')

</form>
