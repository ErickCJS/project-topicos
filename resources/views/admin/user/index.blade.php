@extends('adminlte::page')

@section('title', 'Gestión de usuarios | RSU')

@section('content')
<div class="users-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0"><i class="bi bi-people me-2" aria-hidden="true"></i>Gestión de usuarios</h4>
        <a href="{{ route('users.create') }}" class="btn btn-success"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Nuevo usuario</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body"><div class="table-responsive">
        <table class="table table-striped table-hover align-middle w-100" id="usersTable">
            <thead><tr><th>Fotografía</th><th>Nombres y apellidos</th><th>DNI</th><th>Correo electrónico</th><th>Teléfono</th><th>Tipo de usuario</th><th>Estado</th><th>Última actualización</th><th>Acciones</th></tr></thead>
        </table>
    </div></div></div>
</div>
@stop

@section('css')<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">@stop
@section('js')
<script>
$(function () {
    $('#usersTable').DataTable({
        processing: true, serverSide: true, searchDelay: 350,
        ajax: "{{ route('users.index', [], false) }}",
        columns: [
            {data:'photo', orderable:false, searchable:false}, {data:'name', name:'name'}, {data:'dni', name:'dni'},
            {data:'email', name:'email'}, {data:'phone', name:'phone'}, {data:'type', name:'user_type_id'},
            {data:'status', name:'is_active'}, {data:'updated_at', name:'updated_at'},
            {data:'actions', orderable:false, searchable:false}
        ],
        language: {search:'Buscar:', lengthMenu:'Mostrar _MENU_ registros', info:'Mostrando _START_ a _END_ de _TOTAL_ registros', infoEmpty:'Mostrando 0 a 0 registros', infoFiltered:'(filtrado de _MAX_ registros)', zeroRecords:'No se encontraron usuarios', emptyTable:'Aún no hay usuarios registrados', paginate:{first:'Primero',previous:'Anterior',next:'Siguiente',last:'Último'}}
    });
});
</script>
@stop
