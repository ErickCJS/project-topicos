@php
    $formAction = $motivo && isset($motivo->id) ? route('reason.update', ['reason' => $motivo->id]) : route('reason.store');
    $isEditMode = $motivo && isset($motivo->id);
@endphp

<form id="motivoForm" action="{{ $formAction }}" method="{{ $isEditMode ? 'PUT' : 'POST' }}" data-ajax="true">
    @csrf
    @if($isEditMode)
        @method('PUT')
    @endif

    <div class="modal-header">
        <h5 class="modal-title" id="motivoModalLabel">
            {{ $isEditMode ? 'Editar motivo' : 'Nuevo motivo' }}
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body">
        <div class="mb-3">
            <label for="motivo_name" class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="motivo_name" name="name" value="{{ old('name', $motivo?->name ?? '') }}" maxlength="100" required>
            <div class="invalid-feedback d-none" data-error-for="name"></div>
        </div>

        <div class="mb-3">
            <label for="motivo_description" class="form-label">Descripción</label>
            <textarea class="form-control" id="motivo_description" name="description" rows="4" maxlength="1000">{{ old('description', $motivo?->description ?? '') }}</textarea>
            <div class="invalid-feedback d-none" data-error-for="description"></div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-floppy me-1" aria-hidden="true"></i>
            {{ $isEditMode ? 'Actualizar' : 'Guardar' }}
        </button>
    </div>
</form>
