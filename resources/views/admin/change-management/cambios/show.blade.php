@extends('adminlte::page')

@section('title', 'Detalle del cambio | RSU')

@section('content')
@php
    $labels = ['shift' => 'Turno', 'driver' => 'Conductor', 'helper' => 'Ayudante', 'vehicle' => 'Vehículo', 'rescheduling' => 'Reprogramación', 'bulk' => 'Cambio masivo', 'other' => 'Cambio'];
    $format = function ($value) use (&$format) {
        if (is_array($value)) return collect($value)->map(fn ($item, $key) => $key.': '.$format($item))->implode(' · ');
        if ($value === null || $value === '') return 'Sin información registrada';
        return (string) $value;
    };
    $rows = function (array $values) use ($format) {
        return collect($values)->reject(fn ($value, $key) => in_array($key, ['reason', 'custom_reason', 'description', 'type', 'bulk_operation_id', 'operation_id'], true));
    };
@endphp
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2"><h4 class="mb-0"><i class="bi bi-eye me-2" aria-hidden="true"></i>Detalle del cambio #{{ $cambio->id }}</h4><a href="{{ route('cambios.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Volver</a></div>
    <div class="card shadow-sm mb-3"><div class="card-body"><h5 class="card-title">Información general</h5><div class="row g-3">
        <div class="col-md-3"><strong>Tipo de cambio</strong><div>{{ $typeLabel }}</div></div>
        <div class="col-md-3"><strong>Fecha y hora</strong><div>{{ $cambio->created_at?->format('d/m/Y H:i') ?? 'Sin información registrada' }}</div></div>
        <div class="col-md-3"><strong>Usuario responsable</strong><div>{{ $cambio->user?->name ?? 'Usuario no disponible' }}</div></div>
        <div class="col-md-3"><strong>Programación afectada</strong><div>#{{ $cambio->programming_id }}</div></div>
        @if($operationId)<div class="col-md-6"><strong>Operación masiva</strong><div class="text-break">{{ $operationId }}</div></div>@endif
        @if($cambio->origin_programming_id || $cambio->destination_programming_id)<div class="col-md-6"><strong>Relación de reprogramación</strong><div>Origen: {{ $cambio->origin_programming_id ?? 'Sin información' }} · Destino: {{ $cambio->destination_programming_id ?? 'Sin información' }}</div></div>@endif
        <div class="col-md-6"><strong>Motivo</strong><div>{{ $reason ?: 'Sin información registrada' }}</div></div>
        <div class="col-md-6"><strong>Descripción adicional</strong><div>{{ $description ?: 'Sin información registrada' }}</div></div>
    </div></div></div>
    <div class="row g-3"><div class="col-lg-6"><div class="card h-100 border-warning"><div class="card-header bg-warning-subtle"><strong>Valores anteriores</strong><span class="ms-2 text-body-secondary">{{ $labels[$type] ?? 'Cambio' }}</span></div><div class="card-body"><dl class="row mb-0">@forelse($rows($before) as $key => $value)<dt class="col-sm-4">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt><dd class="col-sm-8">{{ $format($value) }}</dd>@empty<dd class="col-12 text-body-secondary mb-0">Sin información histórica registrada.</dd>@endforelse</dl></div></div></div>
        <div class="col-lg-6"><div class="card h-100 border-success"><div class="card-header bg-success-subtle"><strong>Valores nuevos</strong><span class="ms-2 text-body-secondary">{{ $labels[$type] ?? 'Cambio' }}</span></div><div class="card-body"><dl class="row mb-0">@forelse($rows($after) as $key => $value)<dt class="col-sm-4">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt><dd class="col-sm-8">{{ $format($value) }}</dd>@empty<dd class="col-12 text-body-secondary mb-0">Sin información nueva registrada.</dd>@endforelse</dl></div></div></div>
    </div>
</div>
@stop
