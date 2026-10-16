@extends('adminlte::page')

@section('title', 'Colores | RSUProject')

@section('content')
    <div class="colors-module">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
            <h4 class="mb-0">Listado de Colores</h4>
            <button type="button" class="btn btn-success ms-auto" id="btnNuevoColor">
                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Color
            </button>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle w-100" id="tablaColores">
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Creación</th>
                                <th>Actualización</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        @include('admin.colors.modals')
    </div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop

@section('js')
<script>
    window.coloresConfig = {
        endpoint: "{{ route('vehiclescolor.index') }}",
        token: "{{ csrf_token() }}"
    };
</script>
<script src="{{ asset('js/colores.js') }}"></script>
@stop
