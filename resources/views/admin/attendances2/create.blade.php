{!! Form::open(['route' => 'attendances.store']) !!}
@include('admin.attendances.template.form')
<div class="attendance-modal-footer">
    <button type="submit" class="btn btn-success"><i class="bi bi-floppy me-1"></i>Registrar</button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
</div>
{!! Form::close() !!}
