@extends('adminlte::page')

@section('title', 'Dashboard | RSU')

@section('content_header')
    <div class="rsu-page-heading">
        <div>
            <span class="rsu-eyebrow">GESTIÓN OPERATIVA</span>
            <h1>Dashboard General</h1>
            <p>Monitoreo y gestión de programaciones en tiempo real.</p>
        </div>
        <a href="{{ route('programming.index') }}" class="btn btn-outline-secondary rsu-header-action btn-sm">
            <i class="bi bi-calendar-check" aria-hidden="true"></i>
            Ir al módulo de programación
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
@stop

@section('content')
<div class="rsu-dashboard">
    <section class="rsu-metrics">
        <article class="rsu-metric-card metric-blue"><div><strong>{{ $totalProgramaciones }}</strong><p>Total programaciones</p></div><i class="bi bi-calendar-event rsu-metric-icon"></i></article>
        <article class="rsu-metric-card metric-green"><div><strong>{{ $totalCompletas }}</strong><p>Programaciones completas</p></div><i class="bi bi-check-circle rsu-metric-icon"></i></article>
        <article class="rsu-metric-card metric-red"><div><strong>{{ $totalIncompletas }}</strong><p>Programaciones incompletas</p></div><i class="bi bi-exclamation-triangle rsu-metric-icon"></i></article>
        <article class="rsu-metric-card metric-yellow"><div><strong>{{ $totalFaltantes }}</strong><p>Personal faltante</p></div><i class="bi bi-person-exclamation rsu-metric-icon"></i></article>
    </section>

    <section class="rsu-filter-card">
        <form action="{{ url()->current() }}" method="GET" class="rsu-filter-fields">
            <div class="rsu-filter-group">
                <label for="dashboard-date"><i class="bi bi-calendar3"></i> Fecha</label>
                <input id="dashboard-date" type="date" name="fecha" class="form-control" value="{{ $fechaSeleccionada }}">
            </div>
            <div class="rsu-filter-group">
                <label for="dashboard-shift"><i class="bi bi-clock"></i> Turno</label>
                <select id="dashboard-shift" name="turno" class="form-control">
                    <option value="">Todos los turnos</option>
                    @foreach($turnos as $turno)
                        <option value="{{ $turno->id }}" @selected((string) $turnoSeleccionado === (string) $turno->id)>{{ $turno->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rsu-filter-actions">
                <button type="submit" class="btn rsu-btn-search"><i class="bi bi-search"></i> Buscar</button>
                <a href="{{ url()->current() }}" class="btn rsu-btn-clear" title="Limpiar filtros" aria-label="Limpiar filtros"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </section>

    <div class="rsu-section-heading">
        <div><h3><i class="bi bi-geo-alt"></i> Programaciones por zona</h3><p>Estado de las asignaciones de personal y vehículos.</p></div>
    </div>

    <section class="rsu-programming-grid">
        @forelse($programaciones as $programacion)
            <article class="rsu-programming-card">
                <div class="rsu-zone-header {{ $programacion['complete'] ? 'zone-complete' : 'zone-incomplete' }}"><i class="bi bi-geo-alt-fill"></i>{{ $programacion['zone'] }}</div>
                <div class="rsu-programming-body">
                    <div class="rsu-programming-info">
                        <div class="rsu-info-item"><i class="bi bi-clock text-primary"></i><small>Turno</small><strong>{{ $programacion['shift'] }} @if($programacion['hours'])<span>{{ $programacion['hours'] }}</span>@endif</strong></div>
                        <div class="rsu-info-item"><i class="bi bi-people text-success"></i><small>Grupo</small><strong>{{ $programacion['group'] }}</strong></div>
                        <div class="rsu-info-item"><i class="bi bi-truck text-warning"></i><small>Vehículo</small><strong>{{ $programacion['vehicle'] }}</strong></div>
                    </div>
                    <div class="rsu-status-container"><span class="rsu-status {{ $programacion['complete'] ? 'status-complete' : 'status-incomplete' }}"><i class="bi {{ $programacion['complete'] ? 'bi-check-lg' : 'bi-exclamation-lg' }}"></i>{{ $programacion['complete'] ? 'Completa' : 'Incompleta' }}</span>@if(($programacion['status'] ?? '') === 'Reprogramado')<span class="rsu-status status-rescheduled"><i class="bi bi-arrow-repeat" aria-hidden="true"></i>Reprogramado</span>@endif</div>
                    <div class="rsu-attendance-grid">
                        <div class="rsu-attendance present"><strong><i class="bi bi-person-check"></i> {{ $programacion['present']->count() }}</strong><span>Presentes</span></div>
                        <div class="rsu-attendance missing"><strong><i class="bi bi-person-x"></i> {{ $programacion['missing']->count() }}</strong><span>Faltantes</span></div>
                    </div>
                    <div class="rsu-people-list"><strong>Personal faltante</strong><span>{{ $programacion['missing']->pluck('name')->join(', ') ?: 'Sin personal faltante' }}</span></div>
                    <div class="rsu-card-actions"><button type="button" class="rsu-btn-details" data-programming-id="{{ $programacion['id'] }}" data-summary="{{ json_encode($programacion) }}"><i class="bi bi-eye" aria-hidden="true"></i> Ver detalles</button></div>
                </div>
            </article>
        @empty
            <div class="rsu-empty-state"><i class="bi bi-calendar-x"></i><h4>No hay programaciones registradas</h4><p>No se encontraron programaciones para los filtros seleccionados.</p><a href="{{ route('programming.index') }}" class="btn rsu-btn-programming"><i class="bi bi-plus-lg"></i> Registrar programación</a></div>
        @endforelse
    </section>

    <section class="mt-4">
        <div class="rsu-section-heading">
            <div><h3><i class="bi bi-person-badge"></i> Retenes presentes</h3><p>Personal con asistencia de retén y sin recorrido asignado.</p></div>
        </div>
        <div class="card shadow-sm">
            <div class="card-body">
                @forelse($retenes as $reten)
                    <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom py-2 gap-2">
                        <div><strong>{{ $reten['name'] }}</strong><span class="text-muted ms-2">{{ $reten['dni'] }}</span></div>
                        <div class="text-muted">{{ $reten['shift'] }} · Entrada {{ $reten['time'] }}</div>
                        <span class="badge {{ $reten['available'] ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $reten['available'] ? 'Disponible' : 'Ya asignado' }}</span>
                    </div>
                @empty
                    <span class="text-muted">No hay retenes presentes para los filtros seleccionados.</span>
                @endforelse
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="dashboardProgrammingModal" data-can-edit="{{ Gate::allows('permission', 'programacion.edit') ? '1' : '0' }}" data-details-url="{{ route('programming.modifications', ['programming' => '__ID__']) }}" data-options-url="{{ route('programming.modification-options', ['programming' => '__ID__']) }}" data-update-url="{{ route('programming.update-modifications', ['programming' => '__ID__']) }}" data-related-url="{{ route('programming.related', ['programming' => '__ID__']) }}" tabindex="-1" aria-labelledby="dashboardProgrammingModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered dashboard-modal-dialog"><div class="modal-content dashboard-modal">
        <div class="modal-header"><div><span class="rsu-eyebrow">PROGRAMACIÓN</span><h5 class="modal-title" id="dashboardProgrammingModalTitle">Detalles</h5></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body" id="dashboardProgrammingModalBody"><div class="dashboard-loading"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Cargando...</div></div>
    </div></div>
</div>

@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@stop

@section('js')
<script src="{{ asset('js/dashboard.js') }}"></script>
@stop
