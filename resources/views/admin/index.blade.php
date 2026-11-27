@extends('adminlte::page')

@section('title', 'Inicio | SGRS JLO')

@section('content')
<div class="rsu-welcome-page">
    <header class="rsu-welcome-masthead">
        <a href="{{ route('admin.home') }}" class="rsu-welcome-identity" aria-label="Ir al inicio del Sistema de Gestión RSU">
            <span class="rsu-welcome-crest">
                <img
                    src="https://jloenlinea.munijlo.gob.pe/denuncia-consumidor/assets/logo-DBHCGE0v.jpeg"
                    alt="Escudo municipal de José Leonardo Ortiz"
                    loading="eager"
                    referrerpolicy="no-referrer"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                <i class="fas fa-landmark" aria-hidden="true"></i>
            </span>
            <span class="rsu-welcome-identity-copy">
                <small>MUNICIPALIDAD DISTRITAL DE</small>
                <strong>JOSÉ LEONARDO ORTIZ</strong>
                <span>Sistema de Gestión de Residuos Sólidos</span>
            </span>
        </a>

        <div class="rsu-welcome-date">
            <span class="rsu-welcome-live" aria-hidden="true"></span>
            <span>
                <small>PLATAFORMA INSTITUCIONAL</small>
                <strong>{{ now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</strong>
            </span>
        </div>
    </header>

    <main class="rsu-welcome-main">
        <section class="rsu-welcome-hero">
            <div class="rsu-welcome-hero-copy">
                <span class="rsu-welcome-eyebrow">GESTIÓN PÚBLICA · SERVICIO A LA CIUDAD</span>
                <h1>Una gestión más eficiente para un distrito más limpio.</h1>
                <p>
                    Administra los recursos, el personal y las operaciones de limpieza pública
                    desde un solo lugar.
                </p>
                <a href="#rsu-modules" class="rsu-welcome-hero-link">
                    Explorar módulos
                    <i class="fas fa-arrow-down" aria-hidden="true"></i>
                </a>
            </div>

            <div class="rsu-welcome-hero-image">
                @if(file_exists(public_path('images/dashboard/brigada-rsu.png')))
                    <img
                        src="{{ asset('images/dashboard/brigada-rsu.png') }}"
                        alt="Equipo de trabajo de limpieza pública de José Leonardo Ortiz">
                @else
                    <div class="rsu-welcome-crest-panel">
                        <img
                            src="https://jloenlinea.munijlo.gob.pe/denuncia-consumidor/assets/logo-DBHCGE0v.jpeg"
                            alt="Escudo de la Municipalidad Distrital de José Leonardo Ortiz"
                            loading="lazy"
                            referrerpolicy="no-referrer"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                        <i class="fas fa-landmark" aria-hidden="true"></i>
                    </div>
                @endif
                <div class="rsu-welcome-image-caption">
                    <span>JOSÉ LEONARDO ORTIZ</span>
                    <strong>Compromiso con nuestra ciudad</strong>
                </div>
            </div>
        </section>

        <section class="rsu-welcome-modules" id="rsu-modules">
            <div class="rsu-welcome-section-heading">
                <div>
                    <span class="rsu-welcome-eyebrow">ACCESO AL SISTEMA</span>
                    <h2>¿Qué necesitas gestionar?</h2>
                    <p>Selecciona uno de los módulos habilitados para tu cuenta.</p>
                </div>
                <span class="rsu-welcome-module-count">
                    <strong>{{ count($quickModules) }}</strong>
                    <small>{{ count($quickModules) === 1 ? 'módulo disponible' : 'módulos disponibles' }}</small>
                </span>
            </div>

            <div class="rsu-welcome-module-grid">
                @forelse($quickModules as $module)
                    <a href="{{ route($module['route']) }}" class="rsu-welcome-module">
                        <span class="rsu-welcome-module-icon">
                            <i class="{{ $module['icon'] }}" aria-hidden="true"></i>
                        </span>
                        <span class="rsu-welcome-module-copy">
                            <small>{{ $module['group'] }}</small>
                            <strong>{{ $module['title'] }}</strong>
                            <span>{{ $module['description'] }}</span>
                        </span>
                        <span class="rsu-welcome-module-arrow" aria-hidden="true">
                            <i class="fas fa-arrow-right"></i>
                        </span>
                    </a>
                @empty
                    <div class="rsu-welcome-empty">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <strong>No tienes módulos asignados</strong>
                        <span>Comunícate con el administrador del sistema para solicitar acceso.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </main>

    <footer class="rsu-welcome-footer">
        <span>Municipalidad Distrital de José Leonardo Ortiz</span>
        <span>SGRS JLO <i class="fas fa-circle" aria-hidden="true"></i> Sistema de Gestión RSU</span>
    </footer>
</div>
@stop

@section('css')
<link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
@stop
