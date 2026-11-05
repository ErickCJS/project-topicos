
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

<style>
.rsu-dashboard {
    width: 100%;
    min-width: 0;
    max-width: inherit;
    margin: 0 auto;
    padding: 0 4px 24px;
}

.rsu-page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: inherit;
    margin: 0 auto ;
    gap: 24px;
}

.rsu-page-heading h1 {
    color: #122b45;
    font-size: clamp(24px, 2.2vw, 32px);
    font-weight: 700;
    letter-spacing: -.7px;
    margin: 5px 0 6px;
}

.rsu-page-heading p {
    color: #718198;
    font-size: 13px;
    margin: 0;
}

.rsu-eyebrow {
    color: #087db8;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.2px;
}

.rsu-header-action {
    display: inline-flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 9px;
    border-radius: 6px;
    padding: 10px 14px;
    white-space: nowrap;
}

.rsu-header-action .fa-arrow-right {
    margin-left: 3px;
    font-size: 11px;
}


.rsu-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 285px));
    justify-content: start;
    gap: 14px;
    margin-bottom: 24px;
}

.rsu-metric-card {
    min-width: 0;
    min-height: 100px;
    padding: 17px 18px;
    border: 1px solid #e4e9ef;
    border-left-width: 3px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #202c3b;
    position: relative;
    background: #fff;
}

.metric-blue { border-left-color: #1687bd; }
.metric-green { border-left-color: #35a16b; }
.metric-red { border-left-color: #d65b63; }
.metric-yellow { border-left-color: #d5a52b; }

.rsu-metric-content {
    position: relative;
    z-index: 1;
    min-width: 0;
}

.rsu-metric-content strong {
    display: block;
    font-size: 30px;
    font-weight: 700;
    line-height: 1.2;
}

.rsu-metric-content p {
    color: #667487;
    font-size: 12px;
    font-weight: 500;
    line-height: 1.4;
    margin: 7px 0 0;
}

.rsu-metric-icon {
    flex: 0 0 auto;
    font-size: 25px;
    margin-left: 12px;
}

.metric-blue .rsu-metric-icon { color: #1687bd; }
.metric-green .rsu-metric-icon { color: #35a16b; }
.metric-red .rsu-metric-icon { color: #d65b63; }
.metric-yellow .rsu-metric-icon { color: #b38715; }

.rsu-filter-card {
    background: #fff;
    border: 1px solid #e4e9ef;
    border-radius: 7px;
    padding: 18px;
    margin-bottom: 25px;
}

.rsu-filter-fields {
    display: flex;
    align-items: flex-end;
    gap: 14px;
    flex-wrap: wrap;
}

.rsu-filter-group {
    flex: 0 1 280px;
    min-width: min(240px, 100%);
}

.rsu-filter-group label {
    display: block;
    font-size: 12px;
    color: #344a61;
    margin-bottom: 8px;
    font-weight: 700;
}

.rsu-filter-group label i {
    margin-right: 5px;
}

.rsu-filter-group .form-control {
    width: 100%;
    height: 42px;
    font-size: 12px;
    border-color: #dce4ec;
    border-radius: 5px;
    box-shadow: none;
}

.rsu-filter-group .form-control:focus {
    border-color: #168fc8;
    box-shadow: 0 0 0 3px rgba(22, 143, 200, .12);
}

.rsu-filter-actions {
    display: flex;
    flex: 0 0 auto;
    gap: 8px;
}

.rsu-btn-search {
    min-height: 42px;
    min-width: 122px;
    background: #0789ce;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    border-radius: 5px;
}

.rsu-btn-search:hover,
.rsu-btn-programming:hover,
.rsu-btn-details:hover {
    color: #fff;
    filter: brightness(.94);
}

.rsu-btn-clear {
    min-height: 42px;
    min-width: 42px;
    color: #68798a;
    border: 1px solid #d7dfe7;
    border-radius: 5px;
}


.rsu-section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    gap: 16px;
}

.rsu-section-heading h3 {
    font-size: 19px;
    font-weight: 700;
    color: #1d344c;
    margin: 0 0 5px;
}

.rsu-section-heading h3 i {
    color: #0789ce;
    margin-right: 5px;
}

.rsu-section-heading p {
    font-size: 12px;
    color: #7b899a;
    margin: 0;
}

.rsu-btn-programming {
    background: #0789ce;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 10px 14px;
    border-radius: 5px;
}


.rsu-programming-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(285px, 320px));
    justify-content: start;
    gap: 16px;
}

.rsu-programming-card {
    min-width: 0;
    background: #fff;
    border-radius: 7px;
    overflow: hidden;
    border: 1px solid #e5eaf0;
}

.rsu-zone-header {
    padding: 10px 13px;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    overflow-wrap: anywhere;
}

.zone-complete { background: #2eaa51; }
.zone-incomplete { background: #dc3545; }

.rsu-zone-header i {
    margin-right: 4px;
}

.rsu-programming-body {
    padding: 14px 13px;
}

.rsu-programming-info {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    text-align: center;
    gap: 8px;
}

.rsu-info-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
    min-width: 0;
}

.rsu-info-item > i {
    font-size: 13px;
    margin-bottom: 3px;
}

.rsu-info-item small {
    font-size: 10px;
    color: #8290a0;
}

.rsu-info-item strong {
    font-size: 11px;
    font-weight: 500;
    color: #29425e;
    overflow-wrap: anywhere;
}


/* ESTADOS */
.rsu-status-container {
    display: flex;
    justify-content: center;
    margin: 13px 0 11px;
}

.rsu-status {
    display: inline-block;
    padding: 6px 11px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 700;
    color: #fff;
}

.status-complete { background: #2eaa51; }
.status-incomplete { background: #dc3545; }


/* PRESENTES Y FALTANTES */
.rsu-attendance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 9px;
}

.rsu-attendance {
    min-width: 0;
    padding: 11px 7px;
    color: #fff;
    text-align: center;
    border-radius: 5px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rsu-attendance.present { background: #2eaa51; }
.rsu-attendance.missing { background: #dc3545; }

.rsu-attendance strong {
    font-size: 13px;
}

.rsu-attendance span {
    font-size: 10px;
}


/* BOTÓN DETALLES */
.rsu-btn-details {
    display: block;
    text-align: center;
    background: #0789ce;
    color: #fff;
    border-radius: 7px;
    padding: 10px;
    margin-top: 12px;
    font-size: 11px;
    font-weight: 600;
}

.rsu-btn-details:hover {
    color: #fff;
    text-decoration: none;
    background: #0676b3;
}


/* ESTADO VACÍO */
.rsu-empty-state {
    background: #fff;
    border: 1px dashed #cbd5df;
    border-radius: 6px;
    padding: 45px 15px;
    text-align: center;
    grid-column: 1 / -1;
}

.rsu-empty-state > i {
    font-size: 40px;
    color: #aab7c4;
    margin-bottom: 12px;
}

.rsu-empty-state h4 {
    color: #34495e;
    font-size: 16px;
    font-weight: 600;
}

.rsu-empty-state p {
    color: #8391a2;
    font-size: 12px;
    margin-bottom: 17px;
}


/* RESPONSIVE */
@media (max-width: 700px) {
    .rsu-metrics {
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr));
    }

    .rsu-programming-grid {
        grid-template-columns: repeat(auto-fill, minmax(min(100%, 285px), 1fr));
    }

    .rsu-programming-card {
        min-width: 0;
    }

    .rsu-page-heading {
        align-items: flex-start;
        flex-direction: column;
        gap: 12px;
    }

    .rsu-header-action {
        justify-content: center;
        width: 100%;
    }

    .rsu-filter-actions {
        flex: 1 1 100%;
    }

    .rsu-btn-search {
        flex: 1;
    }
}

@media (max-width: 480px) {
    .rsu-dashboard {
        padding-right: 0;
        padding-left: 0;
    }

    .rsu-metrics {
        gap: 12px;
    }

    .rsu-metric-card {
        min-width: 0;
        min-height: 96px;
        padding: 15px;
    }

    .rsu-filter-card {
        padding: 15px;
    }

    .rsu-filter-group {
        width: 100%;
        min-width: 0;
        max-width: none;
    }

    .rsu-section-heading {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>
@stop
