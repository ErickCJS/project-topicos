{!! Form::model($brand, ['route' => ['brands.update', $brand], 'method' => 'put', 'files' => true]) !!}
@include('admin.brands.template.form')
<br>
<button type="submit" class="btn btn-success"><i class="bi bi-floppy"></i> Actualizar</button>
<button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancelar</button>
{!! Form::close() !!}
