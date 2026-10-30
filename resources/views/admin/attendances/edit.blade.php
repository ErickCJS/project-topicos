<form method="POST" action="{{ route('attendances.update', $attendance) }}">
    @csrf
    @method('PUT')
    @include('admin.attendances.template.form')
    <div class="attendance-modal-footer mt-3">
        <button type="submit" class="btn btn-success"><i class="bi bi-floppy me-1"></i>Actualizar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
    </div>
</form>
