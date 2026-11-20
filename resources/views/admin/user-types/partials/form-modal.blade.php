
@php
    $editing = isset($type);
@endphp

<div class="modal-header users-modal-header">
    <div class="users-modal-heading">
        <span class="users-modal-icon">
            <i class="bi {{ $editing ? 'bi-pencil-square' : 'bi-tag' }}" aria-hidden="true"></i>
        </span>

        <div>
            <h5 class="modal-title mb-0">
                {{ $editing ? 'Editar tipo de usuario' : 'Nuevo tipo de usuario' }}
            </h5>

            <small class="text-muted">
                {{ $editing ? 'Modifica la información de esta clasificación.' : 'Registra una nueva clasificación para los usuarios.' }}
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
    id="userTypeForm"
    action="{{ $editing ? route('user-types.update', $type->id) : route('user-types.store') }}"
    method="POST"
    class="users-modal-form">

    @csrf

    @if($editing)
        @method('PUT')
    @endif

    <div class="modal-body users-form-body">

        <div class="mb-3">
            <label for="user_type_name" class="form-label">
                Nombre del tipo de usuario
                <span class="text-danger">*</span>
            </label>

            <input
                type="text"
                class="form-control"
                id="user_type_name"
                name="name"
                value="{{ old('name', $type->name ?? '') }}"
                maxlength="100"
                placeholder="Ej. Operador"
                autocomplete="off"
                required
            >

            <div class="form-text">
                Este nombre identificará la clasificación del usuario.
            </div>
        </div>

        <div class="mb-3">
            <label for="user_type_description" class="form-label">
                Descripción
            </label>

            <textarea
                class="form-control"
                id="user_type_description"
                name="description"
                rows="4"
                maxlength="255"
                placeholder="Describe brevemente este tipo de usuario..."
            >{{ old('description', $type->description ?? '') }}</textarea>

            <div class="form-text">
                Campo opcional. Máximo 255 caracteres.
            </div>
        </div>

        <div class="alert alert-info d-flex align-items-start gap-2 mb-0">
            <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>

            <div>
                <strong>Información importante</strong>
                <p class="mb-0 small">
                    Los tipos de usuario son únicamente descriptivos.
                    Registrar o modificar una clasificación no modifica
                    los permisos individuales de ningún usuario.
                </p>
            </div>
        </div>

    </div>

    <div class="modal-footer users-modal-footer">

        <button
            type="button"
            class="btn btn-light border"
            data-user-type-back>
            <i class="bi bi-arrow-left me-1"></i>
            Volver al listado
        </button>

        <button type="submit" class="btn btn-success">
            <i class="bi bi-floppy me-1"></i>
            {{ $editing ? 'Guardar cambios' : 'Registrar tipo' }}
        </button>

    </div>

</form>
