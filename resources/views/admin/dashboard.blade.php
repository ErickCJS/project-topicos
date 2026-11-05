
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
            <i class="fas fa-calendar-check" aria-hidden="true"></i>
            Ir al módulo de programación
            <i class="fas fa-arrow-right" aria-hidden="true"></i>
        </a>
    </div>
@stop

@section('content')

@php
    // Colección recibida desde el controlador
    $programaciones = collect($programaciones ?? []);

    // Resumen de programaciones
    $totalProgramaciones = $programaciones->count();

    $completas = $programaciones->filter(function ($p) {
        $estado = strtoupper((string) data_get($p, 'estado', ''));

        return in_array($estado, ['COMPLETO', 'COMPLETA'])
            || ($estado === '' && (int) data_get($p, 'faltantes', 0) === 0);
    });

    $incompletas = $programaciones->filter(function ($p) {
        $estado = strtoupper((string) data_get($p, 'estado', ''));

        return in_array($estado, ['INCOMPLETO', 'INCOMPLETA'])
            || ($estado === '' && (int) data_get($p, 'faltantes', 0) > 0);
    });

    $totalCompletas = $completas->count();
    $totalIncompletas = $incompletas->count();

    $totalFaltantes = $programaciones->sum(function ($p) {
        return (int) data_get($p, 'faltantes', 0);
    });

    $fechaSeleccionada = request('fecha', now()->format('Y-m-d'));
    $turnoSeleccionado = request('turno', '');
@endphp

<div class="rsu-dashboard">

    {{-- INDICADORES PRINCIPALES --}}
    <section class="rsu-metrics">

        <article class="rsu-metric-card metric-blue">
            <div class="rsu-metric-content">
                <strong>{{ $totalProgramaciones }}</strong>
                <p>Total programaciones</p>
            </div>
            <i class="fas fa-calendar-alt rsu-metric-icon"></i>
        </article>

        <article class="rsu-metric-card metric-green">
            <div class="rsu-metric-content">
                <strong>{{ $totalCompletas }}</strong>
                <p>Programaciones completas</p>
            </div>
            <i class="fas fa-check-circle rsu-metric-icon"></i>
        </article>

        <article class="rsu-metric-card metric-red">
            <div class="rsu-metric-content">
                <strong>{{ $totalIncompletas }}</strong>
                <p>Programaciones incompletas</p>
            </div>
            <i class="fas fa-exclamation-triangle rsu-metric-icon"></i>
        </article>

        <article class="rsu-metric-card metric-yellow">
            <div class="rsu-metric-content">
                <strong>{{ $totalFaltantes }}</strong>
                <p>Personal faltante</p>
            </div>
            <i class="fas fa-user-clock rsu-metric-icon"></i>
        </article>

    </section>


    {{-- FILTROS DE BÚSQUEDA --}}
    <section class="rsu-filter-card">

        <form action="{{ url()->current() }}" method="GET">

            <div class="rsu-filter-fields">

                <div class="rsu-filter-group">
                    <label>
                        <i class="fas fa-calendar-day"></i>
                        Fecha:
                    </label>

                    <input
                        type="date"
                        name="fecha"
                        class="form-control"
                        value="{{ $fechaSeleccionada }}"
                    >
                </div>

                <div class="rsu-filter-group">
                    <label>
                        <i class="fas fa-clock"></i>
                        Turno:
                    </label>

                    <select name="turno" class="form-control">
                        <option value="">Todos los turnos</option>
                        <option value="M" {{ $turnoSeleccionado == 'M' ? 'selected' : '' }}>
                            Mañana
                        </option>
                        <option value="T" {{ $turnoSeleccionado == 'T' ? 'selected' : '' }}>
                            Tarde
                        </option>
                        <option value="N" {{ $turnoSeleccionado == 'N' ? 'selected' : '' }}>
                            Noche
                        </option>
                    </select>
                </div>

                <div class="rsu-filter-actions">
                    <button type="submit" class="btn rsu-btn-search">
                        <i class="fas fa-search"></i>
                        Buscar
                    </button>

                    <a href="{{ url()->current() }}" class="btn rsu-btn-clear" title="Limpiar filtros">
                        <i class="fas fa-sync-alt"></i>
                    </a>
                </div>

            </div>

        </form>

    </section>


    {{-- ENCABEZADO DE PROGRAMACIONES --}}
    <div class="rsu-section-heading">
        <div>
            <h3>
                <i class="fas fa-map-marked-alt"></i>
                Programaciones por zona
            </h3>
            <p>Estado de las asignaciones de personal y vehículos.</p>
        </div>

    </div>


    {{-- TARJETAS DE PROGRAMACIONES --}}
    <section class="rsu-programming-grid">

        @forelse($programaciones as $programacion)

            @php
                $zona = data_get($programacion, 'zona', 'Zona sin asignar');
                $turno = data_get($programacion, 'turno', 'Sin turno');
                $grupo = data_get($programacion, 'grupo', 'Sin grupo');

                $vehiculo = data_get(
                    $programacion,
                    'vehiculo.codigo',
                    data_get($programacion, 'vehiculo', 'Sin vehículo')
                );

                $presentes = (int) data_get($programacion, 'presentes', 0);
                $faltantes = (int) data_get($programacion, 'faltantes', 0);

                $estado = strtoupper((string) data_get($programacion, 'estado', ''));

                $estaCompleta = in_array($estado, ['COMPLETO', 'COMPLETA'])
                    || ($estado === '' && $faltantes === 0);
            @endphp

            <article class="rsu-programming-card">

                {{-- CABECERA DE ZONA --}}
                <div class="rsu-zone-header {{ $estaCompleta ? 'zone-complete' : 'zone-incomplete' }}">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $zona }}
                </div>

                <div class="rsu-programming-body">

                    {{-- INFORMACIÓN PRINCIPAL --}}
                    <div class="rsu-programming-info">

                        <div class="rsu-info-item">
                            <i class="fas fa-clock text-primary"></i>
                            <small>Turno</small>
                            <strong>{{ $turno }}</strong>
                        </div>

                        <div class="rsu-info-item">
                            <i class="fas fa-users text-success"></i>
                            <small>Grupo</small>
                            <strong>{{ $grupo }}</strong>
                        </div>

                        <div class="rsu-info-item">
                            <i class="fas fa-truck text-warning"></i>
                            <small>Vehículo</small>
                            <strong>{{ $vehiculo }}</strong>
                        </div>

                    </div>

                    {{-- ESTADO --}}
                    <div class="rsu-status-container">
                        @if($estaCompleta)
                            <span class="rsu-status status-complete">
                                <i class="fas fa-check"></i>
                                Completo
                            </span>
                        @else
                            <span class="rsu-status status-incomplete">
                                <i class="fas fa-exclamation"></i>
                                Incompleto
                            </span>
                        @endif
                    </div>

                    {{-- PERSONAL --}}
                    <div class="rsu-attendance-grid">

                        <div class="rsu-attendance present">
                            <strong>
                                <i class="fas fa-user-check"></i>
                                {{ $presentes }}
                            </strong>
                            <span>Presentes</span>
                        </div>

                        <div class="rsu-attendance missing">
                            <strong>
                                <i class="fas fa-user-times"></i>
                                {{ $faltantes }}
                            </strong>
                            <span>Faltantes</span>
                        </div>

                    </div>

                    {{-- DETALLES --}}
                    <a
                        href="{{ url('/programaciones/' . data_get($programacion, 'id')) }}"
                        class="rsu-btn-details"
                    >
                        <i class="fas fa-eye"></i>
                        Ver detalles
                    </a>

                </div>

            </article>

        @empty

            <div class="rsu-empty-state">
                <i class="fas fa-calendar-times"></i>
                <h4>No hay programaciones registradas</h4>
                <p>No se encontraron programaciones para los filtros seleccionados.</p>

                <a href="{{ route('programming.index') }}" class="btn rsu-btn-programming">
                    <i class="fas fa-plus"></i>
                    Registrar programación
                </a>
            </div>

        @endforelse

    </section>

</div>

@stop


@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">


@stop
