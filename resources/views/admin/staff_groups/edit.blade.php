{!! Form::model($staffgroups, [
    'route' => ['staffgroups.update', $staffgroups->id],
    'method' => 'put',
    'data-id' => $staffgroups->id,
    'data-current-driver' => $staffgroups->driver_id,
    'data-current-helpers' => json_encode($staffgroups->helpers->pluck('id')->values()),
]) !!}

@include('admin.staff_groups.template.form')

<div class="model-modal-footer">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-floppy me-1" aria-hidden="true"></i>
        Actualizar
    </button>

    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>
        Cancelar
    </button>
</div>

{!! Form::close() !!}