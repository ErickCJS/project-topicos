@extends('adminlte::page')

@section('title', 'Inicio | RSU')

@section('content_header')
    <div class="rsu-page-heading">
        <div><span class="rsu-eyebrow">Panel principal</span><h1>Inicio</h1></div>
        <div class="rsu-date"><i class="far fa-calendar-alt"></i> {{ now()->locale('es')->isoFormat('D [de] MMMM, YYYY') }}</div>
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
        <div class="rsu-hero-art" aria-hidden="true"><b></b><span></span><span></span><span></span><em class="fas fa-leaf"></em></div>
    </section>

    <section class="rsu-metrics" aria-label="Resumen operativo">
        <article class="rsu-metric-card"><span class="rsu-icon blue"><i class="fas fa-truck"></i></span><div><strong>12</strong><p>Vehículos operativos</p><small><i class="fas fa-arrow-up"></i> 2 disponibles hoy</small></div></article>
        <article class="rsu-metric-card"><span class="rsu-icon green"><i class="fas fa-route"></i></span><div><strong>28</strong><p>Rutas programadas</p><small><i class="fas fa-arrow-up"></i> 4 esta semana</small></div></article>
        <article class="rsu-metric-card"><span class="rsu-icon amber"><i class="fas fa-users"></i></span><div><strong>85</strong><p>Personal asignado</p><small><i class="fas fa-check"></i> Turnos cubiertos</small></div></article>
        <article class="rsu-metric-card"><span class="rsu-icon violet"><i class="fas fa-clipboard-check"></i></span><div><strong>1,254</strong><p>Servicios ejecutados</p><small><i class="fas fa-arrow-up"></i> 12% este mes</small></div></article>
    </section>

    <section class="rsu-main-grid">
        <div class="rsu-access-area" id="accesos">
            <div class="rsu-section-title"><div><span class="rsu-eyebrow">Navegación</span><h3>Accesos rápidos</h3></div><span></span></div>
            <div class="rsu-access-grid">
                <a href="#" class="rsu-access-card"><span class="rsu-icon blue"><i class="fas fa-users"></i></span><span><b>Gestión de personal</b><small>Cuadrillas, turnos y perfiles</small></span></a>
                <a href="#" class="rsu-access-card"><span class="rsu-icon blue"><i class="fas fa-truck"></i></span><span><b>Control de vehículos</b><small>Mantenimiento y disponibilidad</small></span></a>
                <a href="#" class="rsu-access-card"><span class="rsu-icon green"><i class="fas fa-route"></i></span><span><b>Planificación de rutas</b><small>Recorridos y sectores</small></span></a>
                <a href="#" class="rsu-access-card"><span class="rsu-icon blue"><i class="fas fa-file-contract"></i></span><span><b>Gestión de contratos</b><small>Convenios y proveedores</small></span></a>
                <a href="#" class="rsu-access-card"><span class="rsu-icon violet"><i class="fas fa-user-check"></i></span><span><b>Asistencia</b><small>Registro y puntualidad</small></span></a>
                <a href="#" class="rsu-access-card"><span class="rsu-icon amber"><i class="fas fa-chart-bar"></i></span><span><b>Reportes</b><small>Indicadores y consultas</small></span></a>
            </div>
        </div>
        <aside class="rsu-commitment">
            <img src="{{ asset('images/dashboard/brigada-rsu.png') }}" alt="Brigada municipal realizando labores de limpieza">
            <div></div><section><span>Compromiso JLO</span><h3>Un distrito más limpio, todos los días.</h3><p>Gestión responsable para una ciudad que avanza.</p><a href="#">Conocer más <i class="fas fa-arrow-right"></i></a></section>
        </aside>
    </section>

    <section class="rsu-values">
        <div><span><i class="fas fa-leaf"></i></span><p><b>Ecoeficiente</b><small>Decisiones que cuidan el entorno.</small></p></div>
        <div><span><i class="fas fa-recycle"></i></span><p><b>Sostenible</b><small>Procesos responsables y circulares.</small></p></div>
        <div><span><i class="fas fa-hands-helping"></i></span><p><b>Al servicio de todos</b><small>Una ciudad más ordenada y saludable.</small></p></div>
    </section>
</div>
@stop

@section('css')
<style>
.content-header{padding-bottom:8px}.rsu-page-heading{display:flex;align-items:end;justify-content:space-between;max-width:1200px;margin:auto}.rsu-page-heading h1{font-size:25px;line-height:1;font-weight:700;color:#14261e;margin:6px 0 0;letter-spacing:-.7px}.rsu-eyebrow,.rsu-kicker{color:#5f7469;font-size:10px;font-weight:800;letter-spacing:1.25px;text-transform:uppercase}.rsu-date{color:#7d8c84;font-size:12px;padding-bottom:2px}.rsu-date i{color:#1f7650;margin-right:6px}
.rsu-dashboard{max-width:1200px;margin:0 auto 28px;color:#14251e}.rsu-hero{min-height:263px;overflow:hidden;position:relative;display:flex;align-items:center;padding:38px 46px;border-radius:16px;background:linear-gradient(110deg,#f4f8f4 0%,#f8fbf9 58%,#edf5f0 100%);border:1px solid #e6eee9}.rsu-hero-copy{max-width:520px;position:relative;z-index:2}.rsu-kicker{display:flex;align-items:center;gap:9px}.rsu-kicker i{width:24px;height:2px;background:#29935e;display:inline-block}.rsu-hero h2{font-size:42px;font-weight:650;letter-spacing:-1.75px;line-height:1.04;margin:16px 0 12px}.rsu-hero p{max-width:455px;color:#5f6f68;line-height:1.55;margin:0 0 20px;font-size:14px}.rsu-primary-action{background:#176542;color:#fff!important;border-radius:8px;display:inline-flex;align-items:center;gap:12px;padding:10px 16px;font-size:12px;font-weight:700}.rsu-primary-action:hover{text-decoration:none;background:#0e4c30}
.rsu-hero-art{position:absolute;inset:0 0 0 54%;opacity:.75;overflow:hidden}.rsu-hero-art b{position:absolute;width:112px;height:112px;border:1px solid #cdded3;border-radius:50%;right:65px;top:30px}.rsu-hero-art span{position:absolute;bottom:48px;border:1px solid #cadbd0;background:rgba(245,250,246,.5)}.rsu-hero-art span:nth-of-type(1){width:85px;height:130px;right:100px}.rsu-hero-art span:nth-of-type(2){width:53px;height:85px;right:195px}.rsu-hero-art span:nth-of-type(3){width:40px;height:108px;right:260px}.rsu-hero-art:after{content:"";position:absolute;height:120px;width:125%;bottom:-70px;left:-12%;background:#dcebe1;border-radius:50% 50% 0 0;transform:rotate(-5deg)}.rsu-hero-art em{position:absolute;right:26px;bottom:16px;color:#94b9a2;font-size:56px;transform:rotate(28deg)}
.rsu-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:16px 0 26px}.rsu-metric-card{background:#fff;border:1px solid #e9eeeb;box-shadow:0 5px 16px rgba(22,50,36,.035);border-radius:12px;padding:15px 16px;display:flex;align-items:center;gap:12px;min-width:0}.rsu-icon{width:42px;height:42px;border-radius:12px;flex:0 0 42px;display:grid;place-items:center;font-size:17px}.blue{background:#edf5ff;color:#2471bc}.green{background:#edf8f0;color:#27834a}.amber{background:#fff5e3;color:#dc9211}.violet{background:#f2efff;color:#7754d6}.rsu-metric-card strong{display:block;font-size:22px;line-height:1;letter-spacing:-.6px}.rsu-metric-card p{margin:4px 0 2px;font-size:11px;color:#5c6b64;white-space:nowrap}.rsu-metric-card small{color:#52a06a;font-size:10px;white-space:nowrap}.rsu-metric-card small i{font-size:8px}
.rsu-main-grid{display:grid;grid-template-columns:minmax(0,1fr) 282px;gap:20px;align-items:stretch}.rsu-section-title{display:flex;align-items:end;justify-content:space-between;margin:0 0 11px}.rsu-section-title h3{font-size:21px;font-weight:700;letter-spacing:-.6px;margin:3px 0 0}.rsu-section-title>span{height:7px;width:7px;border-radius:50%;background:#62b87a;margin:0 3px 5px}.rsu-access-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.rsu-access-card{min-height:104px;display:flex;align-items:flex-start;gap:10px;padding:16px 13px;border-radius:11px;background:#fff;color:#172921!important;border:1px solid #e8edeb;transition:.2s}.rsu-access-card:hover{text-decoration:none;transform:translateY(-2px);box-shadow:0 9px 22px rgba(22,60,38,.08);border-color:#cbe0d2}.rsu-access-card .rsu-icon{width:35px;height:35px;flex-basis:35px;border-radius:10px;font-size:14px}.rsu-access-card b{display:block;font-size:11px;margin:2px 0 5px;line-height:1.15}.rsu-access-card small{display:block;font-size:10px;line-height:1.35;color:#7a8781}
.rsu-commitment{overflow:hidden;min-height:273px;position:relative;border-radius:14px;background:#103f2d}.rsu-commitment>img{position:absolute;width:100%;height:100%;object-fit:cover;object-position:73% center}.rsu-commitment>div{position:absolute;inset:0;background:linear-gradient(90deg,rgba(8,43,28,.97),rgba(11,61,39,.81) 43%,rgba(12,58,38,.05))}.rsu-commitment section{position:absolute;z-index:1;left:21px;right:65px;bottom:20px;color:#fff}.rsu-commitment section>span{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#a9dab9;font-weight:700}.rsu-commitment h3{font-size:22px;line-height:1.08;letter-spacing:-.7px;margin:8px 0}.rsu-commitment p{font-size:10px;line-height:1.45;margin:0 0 11px;color:#d4e7da}.rsu-commitment a{font-size:10px;font-weight:700;color:#fff!important}
.rsu-values{background:#fff;border:1px solid #e7eeea;border-radius:13px;margin-top:20px;display:grid;grid-template-columns:repeat(3,1fr);padding:18px 24px}.rsu-values>div{display:flex;gap:12px;align-items:center;padding:0 20px;border-right:1px solid #edf0ee}.rsu-values>div:first-child{padding-left:0}.rsu-values>div:last-child{border:0}.rsu-values>div>span{display:grid;place-items:center;width:37px;height:37px;border-radius:50%;background:#ecf7ef;color:#248047;font-size:17px;flex:0 0 37px}.rsu-values>div:nth-child(2)>span{background:#eff8e9;color:#52a326}.rsu-values>div:nth-child(3)>span{background:#eef5ff;color:#397ac5}.rsu-values p{margin:0}.rsu-values b{display:block;font-size:11px}.rsu-values small{display:block;color:#718078;font-size:10px;margin-top:3px;line-height:1.3}
@media(max-width:991px){.rsu-metrics{grid-template-columns:repeat(2,1fr)}.rsu-main-grid{grid-template-columns:1fr}.rsu-commitment{min-height:240px}.rsu-access-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.rsu-page-heading{display:block}.rsu-date{margin-top:10px}.rsu-hero{padding:28px 23px;min-height:280px}.rsu-hero h2{font-size:34px}.rsu-hero-art{left:35%;opacity:.38}.rsu-metrics,.rsu-access-grid{grid-template-columns:1fr}.rsu-values{grid-template-columns:1fr;gap:15px}.rsu-values>div{padding:0;border:0}.rsu-metric-card p{white-space:normal}}
</style>
@stop
