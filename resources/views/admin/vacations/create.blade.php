<form action="{{ route('vacations.store') }}" method="POST" class="vacation-form" data-vacation-id="">
    @csrf
    <div class="modal-header vacation-modal-header">
        <div class="vacation-modal-heading">
            <span class="vacation-modal-icon" aria-hidden="true"><i class="bi bi-calendar2-plus"></i></span>
            <div><h5 class="modal-title mb-0" id="vacationFormModalLabel">Nueva vacación</h5></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body vacation-modal-body">
        @include('admin.vacations.partials.form')
    </div>
    <div class="modal-footer vacation-modal-footer">
        <button type="submit" class="btn btn-success"><i class="bi bi-floppy me-1"></i>Guardar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    </div>
</form>
