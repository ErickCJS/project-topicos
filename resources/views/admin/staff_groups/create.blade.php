{!! Form::open(['route' => 'staffgroups.store']) !!}
@include('admin.staff_groups.template.form')
<div class="model-modal-footer mt-3">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-floppy me-1" aria-hidden="true"></i>Registrar
    </button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
    </button>
</div>
{!! Form::close() !!}
