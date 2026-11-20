{!! Form::model($shift, [
    'route' => ['shifts.update', $shift],
    'method' => 'put',
]) !!}
@include('admin.shifts.template.form')
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
    <button type="submit" class="btn btn-success">
        <i class="bi bi-floppy me-1" aria-hidden="true"></i>Guardar cambios
    </button>
</div>
{!! Form::close() !!}
