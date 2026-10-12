{!! Form::open(['route' => 'vehicles.store', 'files' => true]) !!}
@include('admin.vehicles.template.form')
<br>
<button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Registrar</button>
<button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="fas fa-times-circle"></i> Cancelar</button>
{!! Form::close() !!}
