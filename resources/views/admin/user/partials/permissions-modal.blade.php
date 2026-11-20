<div class="modal-header users-modal-header">
    <div class="users-modal-heading">
        <span class="users-modal-icon" aria-hidden="true">
            <i class="bi bi-shield-lock"></i>
        </span>

        <div>
            <h5 class="modal-title mb-0">
                Permisos individuales
            </h5>

            <small class="text-muted">
                Configuración de accesos para
                {{ trim($user->name . ' ' . $user->last_name) }}
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

<div class="modal-body permission-modal-body">
    @include('admin.user.partials.permissions-matrix')
</div>
