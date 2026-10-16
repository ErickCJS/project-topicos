@extends('adminlte::page')

@section('title', 'Inicio | RSU')

@section('content_header')
    <div class="rsu-page-heading">
        <div><span class="rsu-eyebrow">Panel principal</span><h1>Inicio</h1></div>
        <div class="rsu-date" aria-label="Fecha de la jornada operativa de residuos sólidos urbanos">
            <i class="far fa-calendar-alt" aria-hidden="true"></i>
            <span>
                <small>Jornada operativa RSU</small>
                <strong>{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</strong>
            </span>
        </div>
    </div>
@stop

@section('content')
<div class="rsu-dashboard">
    <section class="rsu-hero">
        <div class="rsu-hero-copy">
            <span class="rsu-kicker"><i></i>Sistema de gestión de residuos sólidos urbanos</span>
            <h2>Gestión que cuida<br>nuestro distrito.</h2>
            <p>Consulta el estado operativo y accede rápidamente a los módulos disponibles para ti.</p>
            <a href="#accesos" class="rsu-primary-action">Ver módulos <i class="fas fa-arrow-right"></i></a>
        </div>
<div class="rsu-hero-art" aria-hidden="true">
    <div class="rsu-eco-circle">
        <i class="fas fa-recycle"></i>
    </div>
</div>
    </section>

    <section class="rsu-metrics" aria-label="Resumen operativo">
        <article class="rsu-metric-card"><span class="rsu-icon blue"><i class="fas fa-truck"></i></span><div><strong>12</strong><p>Vehículos operativos</p><small><i class="fas fa-arrow-up"></i> 2 disponibles hoy</small></div></article>
        <article class="rsu-metric-card"><span class="rsu-icon green"><i class="fas fa-route"></i></span><div><strong>28</strong><p>Rutas programadas</p><small><i class="fas fa-arrow-up"></i> 4 esta semana</small></div></article>
        <article class="rsu-metric-card"><span class="rsu-icon amber"><i class="fas fa-users"></i></span><div><strong>85</strong><p>Personal asignado</p><small><i class="fas fa-check"></i> Turnos cubiertos</small></div></article>
        <article class="rsu-metric-card"><span class="rsu-icon violet"><i class="fas fa-clipboard-check"></i></span><div><strong>1,254</strong><p>Servicios ejecutados</p><small><i class="fas fa-arrow-up"></i> 12% este mes</small></div></article>
    </section>

    <!-- RSU-INSTITUCIONAL-V1 -->
    <section class="rsu-main-grid">
        <div class="rsu-institutional" id="accesos">

            <div class="rsu-institutional-header">
                <i class="fas fa-landmark"></i>
                <span>Municipalidad Distrital de Jos&eacute; Leonardo Ortiz</span>
                <span class="rsu-institutional-tag">GESTI&Oacute;N RSU</span>
            </div>

            <div class="rsu-institutional-body">

                <div class="rsu-institutional-photo">
                    @if(file_exists(public_path('images/dashboard/brigada-rsu.png')))
                        <img src="{{ asset('images/dashboard/brigada-rsu.png') }}"
                             alt="Equipo de limpieza publica">
                    @else
                        <div class="rsu-photo-placeholder">
                            <i class="fas fa-city"></i>
                            <span>Jos&eacute; Leonardo Ortiz</span>
                        </div>
                    @endif

                    <div class="rsu-photo-caption">
                        <i class="fas fa-leaf"></i>
                        Por un distrito m&aacute;s limpio
                    </div>
                </div>

                <div class="rsu-institutional-info">
                    <div class="rsu-info-top">
                        <span class="rsu-info-kicker">BIENVENIDO AL SISTEMA</span>
                        <h3>Comprometidos con nuestro distrito</h3>
                        <p>
                            Plataforma para administrar los recursos,
                            el personal y las operaciones relacionadas
                            con la gesti&oacute;n de residuos s&oacute;lidos urbanos.
                        </p>
                    </div>

                    <h4 class="rsu-module-title">
                        <i class="fas fa-th-large"></i>
                        M&oacute;dulos del sistema
                    </h4>

                    <div class="rsu-module-list">

                        <div class="rsu-module-link rsu-module-pending">
                            <i class="fas fa-users"></i>
                            <span>Gesti&oacute;n de personal</span>
                            <small>Pr&oacute;ximamente</small>
                        </div>

                        <a href="{{ route('vehicles.index') }}" class="rsu-module-link">
                            <i class="fas fa-truck"></i>
                            <span>Control de veh&iacute;culos</span>
                            <i class="fas fa-arrow-right rsu-module-arrow"></i>
                        </a>

                        <div class="rsu-module-link rsu-module-pending">
                            <i class="fas fa-route"></i>
                            <span>Planificaci&oacute;n de rutas</span>
                            <small>Pr&oacute;ximamente</small>
                        </div>

                        <div class="rsu-module-link rsu-module-pending">
                            <i class="fas fa-file-contract"></i>
                            <span>Gesti&oacute;n de contratos</span>
                            <small>Pr&oacute;ximamente</small>
                        </div>

                        <div class="rsu-module-link rsu-module-pending">
                            <i class="fas fa-user-check"></i>
                            <span>Asistencia</span>
                            <small>Pr&oacute;ximamente</small>
                        </div>

                        <div class="rsu-module-link rsu-module-pending">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reportes</span>
                            <small>Pr&oacute;ximamente</small>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>
    <section class="rsu-values">
        <div><span><i class="fas fa-leaf"></i></span><p><b>Ecoeficiente</b><small>Decisiones que cuidan el entorno.</small></p></div>
        <div><span><i class="fas fa-recycle"></i></span><p><b>Sostenible</b><small>Procesos responsables y circulares.</small></p></div>
        <div><span><i class="fas fa-hands-helping"></i></span><p><b>Al servicio de todos</b><small>Una ciudad más ordenada y saludable.</small></p></div>
    </section>
</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop
