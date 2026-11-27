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


    <!-- RSU-INSTITUCIONAL-V1 -->
    <section class="rsu-main-grid">
        <div class="rsu-institutional" id="accesos">

            <div class="rsu-institutional-header">
                <i class="fas fa-landmark"></i>
                <span>Municipalidad Distrital de José Leonardo Ortiz</span>
                <span class="rsu-institutional-tag">GESTIÓN RSU</span>
            </div>

            <div class="rsu-institutional-body">

                <div class="rsu-institutional-photo">
                    @if(file_exists(public_path('images/dashboard/brigada-rsu.png')))
                        <img src="{{ asset('images/dashboard/brigada-rsu.png') }}"
                             alt="Equipo de limpieza publica">
                    @else
                        <div class="rsu-photo-placeholder rsu-municipal-emblem">
                            <img src="https://jloenlinea.munijlo.gob.pe/denuncia-consumidor/assets/logo-DBHCGE0v.jpeg"
                                 alt="Escudo de la Municipalidad Distrital de José Leonardo Ortiz"
                                 loading="lazy"
                                 referrerpolicy="no-referrer">
                            <span>José Leonardo Ortiz</span>
                        </div>
                    @endif

                    <div class="rsu-photo-caption">
                        <i class="fas fa-leaf"></i>
                        Por un distrito más limpio
                    </div>
                </div>

                <div class="rsu-institutional-info">
                    <div class="rsu-info-top">
                        <span class="rsu-info-kicker">BIENVENIDO AL SISTEMA</span>
                        <h3>Comprometidos con nuestro distrito</h3>
                        <p>
                            Plataforma para administrar los recursos,
                            el personal y las operaciones relacionadas
                            con la gestión de residuos sólidos urbanos.
                        </p>
                    </div>

                    <h4 class="rsu-module-title">
                        <i class="fas fa-th-large"></i>
                        Módulos del sistema
                    </h4>

                    <div class="rsu-module-list">
                        @forelse($quickModules as $module)
                            <a href="{{ route($module['route']) }}" class="rsu-module-link">
                                <i class="{{ $module['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $module['title'] }}</span>
                                <i class="fas fa-arrow-right rsu-module-arrow" aria-hidden="true"></i>
                            </a>
                        @empty
                            <div class="rsu-module-link rsu-module-pending">
                                <i class="fas fa-lock" aria-hidden="true"></i>
                                <span>No tienes módulos asignados</span>
                            </div>
                        @endforelse
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
