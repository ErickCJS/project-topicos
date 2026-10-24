<form action="{{ route('vacations.update', $vacation) }}" method="POST" class="vacation-form" data-vacation-id="{{ $vacation->id }}">
    @csrf
    @method('PUT')
    <div class="modal-header vacation-modal-header">
        <div class="vacation-modal-heading">
            <span class="vacation-modal-icon" aria-hidden="true"><i class="bi bi-pencil-square"></i></span>
            <div>
                <h5 class="modal-title mb-0" id="vacationFormModalLabel">
                    {{ $vacation->isHistorical() ? 'Regularizar vacación histórica' : 'Editar vacación' }}
                </h5>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body vacation-modal-body">
        @include('admin.vacations.partials.form')
    </div>
    <div class="modal-footer vacation-modal-footer">
        <button type="submit" class="btn btn-success"><i class="bi bi-floppy me-1"></i>Actualizar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </div>
</form>
