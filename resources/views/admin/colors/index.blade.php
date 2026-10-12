@extends('adminlte::page')

@section('title', 'Colores')

@section('content')
    <div class="card">
        <div class="card-header d-flex">
            <div class="w-50">
                <h4>Listado de Colores</h4>
            </div>
            <div class="w-50 d-flex justify-content-end">
                <button type="button" class="btn btn-success" id="btnNuevoColor">
                    <i class="fas fa-plus-circle"></i> Nuevo Color
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="tablaColores">
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
</div>

@include('admin.colors.modals')
@stop

@section('css')
<style>
    .color-swatch { display: inline-block; width: 34px; height: 25px; border: 1px solid var(--bs-border-color); border-radius: .25rem; vertical-align: middle; }
    .color-actions { display: flex; justify-content: center; gap: .4rem; }
    .color-actions .btn { min-width: 36px; }
    .color-preview { display: flex; min-height: 48px; align-items: center; justify-content: center; color: #fff; border: 1px solid var(--bs-border-color); border-radius: .25rem; font-size: .85rem; font-weight: 700; text-shadow: 0 1px 2px #0008; transition: background-color .15s ease; }
    .color-picker { width: 3.2rem; min-width: 3.2rem; padding: .25rem; }
</style>
@stop

@section('js')
<script>
    window.coloresConfig = {
        endpoint: @json(route('vehiclescolor.index')),
        token: @json(csrf_token())
    };
</script>
<script src="{{ asset('js/colores.js') }}"></script>
@stop
