{!! Form::model($model, [
    'route' => ['brandsmodel.update', $model],
    'method' => 'put',
]) !!}
@include('admin.model.template.form')
<div class="model-modal-footer">
    <button type="submit" class="btn btn-success">
        <i class="bi bi-floppy me-1" aria-hidden="true"></i>Actualizar
    </button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
    </button>
</div>
{!! Form::close() !!}
