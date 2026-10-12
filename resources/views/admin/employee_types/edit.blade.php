{!! Form::model($type, [
    'route' => ['employee-types.update', $type->id],
    'method' => 'put'
]) !!}

@include('admin.employee_types.template.form')

<br>
<button type="submit" class="btn btn-success">
    <i class="fas fa-save"></i> Actualizar
</button>
<button type="button" class="btn btn-danger" data-bs-dismiss="modal">
    <i class="fas fa-times-circle"></i> Cancelar
</button>

{!! Form::close() !!}