@extends('adminlte::page')

@section('title', 'Nuevo cambio masivo | RSU')

@section('css')
<link rel="stylesheet" href="{{ asset('css/programming.css') }}">
@stop

@section('content')
<div class="change-management-module">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 pb-2 border-bottom">
        <div><h4 class="mb-1"><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Nuevo cambio masivo</h4><div class="text-body-secondary">Gestione cambios sobre programaciones programadas desde el módulo Cambios.</div></div>
        <a href="{{ route('cambios.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver a cambios</a>
    </div>
    @include('admin.programming.partials.bulk-modifications-modal', ['embedded' => true])
    <div class="card shadow-sm"><div class="card-body text-center py-5"><i class="bi bi-arrow-left-right display-5 text-primary" aria-hidden="true"></i><h5 class="mt-3">Configure y confirme el cambio masivo</h5><p class="text-body-secondary mb-4">La operación conserva las validaciones, conflictos, historial y confirmación transaccional existentes.</p><button type="button" class="btn btn-warning" id="bulkModification" data-bs-toggle="modal" data-bs-target="#bulkModificationModal"><i class="bi bi-pencil-square me-1"></i>Abrir formulario de cambio masivo</button></div></div>
</div>
@stop

@section('js')
<script>
document.getElementById('bulkModificationModal')?.setAttribute('data-endpoint-base', @json(url('/admin/cambios/masivos')));
document.getElementById('bulkModificationModal')?.setAttribute('data-reload-url', @json(route('cambios.index')));
window.programmingBulkReasons = @json($reasons);
</script>
<script src="{{ asset('js/bulk-programming-modifications.js') }}"></script>
@stop
