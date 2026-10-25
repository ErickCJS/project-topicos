@extends('adminlte::page')

@section('title', 'Programaciones | RSU')
@section('plugins.DatatablesButtons', true)
@section('plugins.Select2', true)

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/programming.css') }}">
@stop

@section('content')
<div class="programming-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <h4 class="mb-0"><i class="bi bi-calendar-week me-2" aria-hidden="true"></i>Lista de Programaciones</h4>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.home') }}" class="btn btn-outline-secondary"><i class="bi bi-grid me-1"></i>Ir al Dashboard</a>
            <button type="button" class="btn btn-success" id="newProgramming"><i class="bi bi-plus-circle me-1"></i>Nueva programación</button>
            <button type="button" class="btn btn-outline-secondary" id="bulkProgramming"><i class="bi bi-stack me-1"></i>Programación masiva</button>
        </div>
    </div>
    <p class="small text-body-secondary mb-3"><i class="bi bi-info-circle me-1"></i>Vista de demostración con datos de ejemplo. Los cambios duran hasta recargar la página.</p>

    <div class="card mb-3"><div class="card-body">
        <form id="programmingFilters" class="row g-3 align-items-end">
            <div class="col-12 col-md-6 col-xl-2"><label for="filterStart" class="form-label">Fecha de inicio</label><input type="date" class="form-control" id="filterStart"></div>
            <div class="col-12 col-md-6 col-xl-2"><label for="filterEnd" class="form-label">Fecha de fin</label><input type="date" class="form-control" id="filterEnd"></div>
            <div class="col-12 col-md-6 col-xl-3"><label for="filterZone" class="form-label">Zona</label><select class="form-select" id="filterZone"><option value="">Todas las zonas</option><option>Zona Norte</option><option>Zona Centro</option><option>Zona Sur</option></select></div>
            <div class="col-12 col-md-6 col-xl-3"><label for="filterShift" class="form-label">Turno</label><select class="form-select" id="filterShift"><option value="">Todos los turnos</option><option>Mañana</option><option>Tarde</option><option>Noche</option></select></div>
            <div class="col-12 col-xl-2"><button type="reset" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg me-1"></i>Limpiar</button></div>
            <div class="col-12 small text-danger d-none" id="filterError" role="alert">La fecha de fin debe ser igual o posterior a la de inicio.</div>
        </form>
    </div></div>

    <div class="card"><div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <div id="programmingExports" class="d-flex flex-wrap gap-2"></div>
            <div class="dropdown"><button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="bi bi-layout-three-columns me-1"></i>Columnas</button><div class="dropdown-menu p-3" id="programmingColumns"></div></div>
        </div>
        <div class="table-responsive">
            <table id="programmingTable" class="table table-striped table-hover align-middle w-100">
                <thead><tr><th>Fecha</th><th>Estado</th><th>Zona</th><th>Turno</th><th>Vehículo</th><th>Conductor</th><th>Ayudantes</th><th>Acciones</th></tr></thead><tbody></tbody>
            </table>
        </div>
    </div></div>

    @include('admin.programming.partials.form-modal')

    <div class="modal fade" id="programmingDetailModal" tabindex="-1" aria-labelledby="programmingDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="programmingDetailTitle">Detalle de programación</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <div class="modal-body" id="programmingDetailBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
        </div></div>
    </div>
</div>
@stop

@section('js')
<script src="{{ asset('js/programming.js') }}"></script>
@stop
