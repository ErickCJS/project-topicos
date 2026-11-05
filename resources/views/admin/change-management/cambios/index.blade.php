@extends('adminlte::page')

@section('title', 'Cambios | RSU')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h4 class="mb-0"><i class="bi bi-arrow-left-right me-2" aria-hidden="true"></i>Cambios</h4>
        <a href="{{ route('cambios.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Nuevo cambio
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-0">Módulo de gestión de cambios registrados en la programación.</p>
        </div>
    </div>
</div>
@stop
