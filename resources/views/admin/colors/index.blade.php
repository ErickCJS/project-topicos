@extends('adminlte::page')

@section('title', 'Colores')

@section('content')
<div class="colors-module">
    <div class="colors-heading d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-palette-fill me-2 text-primary"></i>Lista de Colores</h1>
            <p class="text-muted mb-0">Muestra los colores registrados que se asignarán a los vehículos.</p>
        </div>
        <button type="button" class="btn btn-primary" id="btnNuevoColor">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Color
        </button>
    </div>

    <div class="card colors-card border-0 shadow-sm">
        <div class="card-body p-3 p-lg-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle w-100 mb-0" id="tablaColores">
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
    .colors-module { padding: .25rem 0 1.5rem; }
    .colors-heading h1 { color: #23364d; font-weight: 700; }
    .colors-heading p { font-size: .92rem; }
    .colors-card { border-radius: .75rem; }
    #tablaColores thead th { background: #f4f7fb; color: #44546a; font-size: .78rem; font-weight: 700; text-transform: uppercase; white-space: nowrap; border-bottom: 1px solid #e5eaf1; }
    #tablaColores tbody td { color: #344054; }
    .color-swatch { display: inline-block; width: 34px; height: 25px; border: 1px solid #d7dce3; border-radius: .35rem; vertical-align: middle; box-shadow: 0 1px 2px #00000012; }
    .color-actions { display: flex; justify-content: center; gap: .4rem; }
    .color-actions .btn { min-width: 36px; }
    .color-modal .modal-content { overflow: hidden; border: 0; border-radius: .7rem; }
    .color-modal .modal-header { color: #fff; background: #075985; border: 0; }
    .color-modal .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
    .color-modal .form-label { color: #344054; font-size: .88rem; font-weight: 650; }
    .color-preview { display: flex; min-height: 48px; align-items: center; justify-content: center; color: #fff; border-radius: .4rem; font-size: .85rem; font-weight: 700; text-shadow: 0 1px 2px #0008; transition: background-color .15s ease; }
    .color-picker { width: 3.2rem; min-width: 3.2rem; padding: .25rem; }
    @media (max-width: 575.98px) { .colors-heading .btn { width: 100%; } }
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
