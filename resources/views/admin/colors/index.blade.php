@extends('adminlte::page')

@section('title', 'Colores | RSUProject')

@section('content')
    <div class="colors-module">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-3 flex-wrap">
                <h4 class="mb-0">Listado de Colores</h4>
                <button type="button" class="btn btn-success ms-auto" id="btnNuevoColor">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo Color
                </button>
            </div>
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
<style>
    .colors-module #tablaColores th,
    .colors-module #tablaColores td {
        vertical-align: middle;
    }

    .colors-module .color-modal .modal-content {
        overflow: hidden;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .18);
    }

    .colors-module .color-modal .color-modal-header {
        min-height: 76px;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
    }

    .colors-module .color-modal-heading {
        display: flex;
        align-items: center;
        gap: .85rem;
        min-width: 0;
    }

    .colors-module .color-modal-heading small {
        display: block;
        margin-top: .2rem;
        color: var(--bs-secondary-color);
    }

    .colors-module .color-modal-icon {
        display: inline-grid;
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        place-items: center;
        color: var(--bs-info-text-emphasis);
        background-color: var(--bs-info-bg-subtle);
        border: 1px solid var(--bs-info-border-subtle);
        border-radius: .65rem;
        font-size: 1.15rem;
    }

    .colors-module .color-modal .color-modal-body {
        padding: 1.5rem;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
    }

    .colors-module .color-form-intro {
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--bs-border-color);
    }

    .colors-module .color-form-intro h6,
    .colors-module .color-modal .form-label {
        color: var(--bs-body-color);
    }

    .colors-module .color-form-intro p,
    .colors-module .color-modal .form-text,
    .colors-module .color-counter {
        color: var(--bs-secondary-color);
    }

    .colors-module .color-modal .form-label {
        margin-bottom: .45rem;
    }

    .colors-module .color-modal .form-control {
        min-height: 42px;
        color: var(--bs-body-color);
        background-color: var(--bs-body-bg);
        border-color: var(--bs-border-color);
    }

    .colors-module .color-modal textarea.form-control {
        min-height: 104px;
    }

    .colors-module .color-modal .form-control::placeholder {
        color: var(--bs-secondary-color);
        opacity: .8;
    }

    .colors-module .color-modal .form-control:focus {
        border-color: rgba(var(--bs-info-rgb), .8);
        box-shadow: 0 0 0 .2rem rgba(var(--bs-info-rgb), .16);
    }

    .colors-module .color-modal .color-modal-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .65rem;
        padding: 1rem 1.25rem;
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border-top: 1px solid var(--bs-border-color);
    }

    .colors-module .color-preview,
    .colors-module .color-detail-card {
        color: var(--bs-body-color);
        background-color: var(--bs-tertiary-bg);
        border: 1px solid var(--bs-border-color);
        border-radius: .6rem;
    }

    .colors-module .color-preview {
        display: flex;
        min-height: 72px;
        align-items: center;
        justify-content: center;
        padding: .75rem;
        font-size: .95rem;
        font-weight: 700;
        transition: background-color .15s ease, color .15s ease;
    }

    .colors-module .color-preview.is-incomplete {
        color: var(--bs-secondary-color) !important;
        background-color: var(--bs-tertiary-bg) !important;
        border-style: dashed;
        text-shadow: none !important;
    }

    .colors-module .color-picker {
        width: 3.25rem;
        min-width: 3.25rem;
        padding: .25rem;
        cursor: pointer;
    }

    .colors-module .color-detail-card {
        height: 100%;
        padding: 1rem;
    }

    .colors-module .color-detail-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--bs-secondary-color);
        font-size: .78rem;
        font-weight: 600;
        letter-spacing: .035em;
        text-transform: uppercase;
    }

    .colors-module .color-detail-value {
        color: var(--bs-body-color);
        font-weight: 600;
    }

    .colors-module .color-detail-description {
        color: var(--bs-body-color);
        white-space: pre-wrap;
    }

    .colors-module .color-detail-preview {
        display: grid;
        min-height: 110px;
        place-items: center;
        margin-top: .5rem;
        padding: 1rem;
        border: 1px solid var(--bs-border-color);
        border-radius: .5rem;
        font-weight: 700;
    }

    .colors-module .color-swatch {
        display: inline-block;
        width: 34px;
        height: 25px;
        border: 1px solid var(--bs-border-color);
        border-radius: .25rem;
        vertical-align: middle;
    }

    .colors-module .color-actions {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
    }

    .colors-module .color-actions .color-action-btn {
        display: inline-flex;
        width: 36px;
        min-width: 36px;
        height: 31px;
        flex: 0 0 36px;
        align-items: center;
        justify-content: center;
        padding: 0;
        border-width: 1px;
        border-style: solid;
        border-radius: .25rem;
    }

    .colors-module .color-actions .color-action-btn i {
        line-height: 1;
    }

    html.dark .colors-module .color-modal .modal-content,
    html[data-bs-theme="dark"] .colors-module .color-modal .modal-content {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .5);
    }

    @media (max-width: 575.98px) {
        .colors-module .color-modal .color-modal-body {
            padding: 1rem;
        }

        .colors-module .color-modal .color-modal-footer {
            flex-wrap: wrap;
        }
    }
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
