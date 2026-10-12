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
<style>
.content-header{padding-bottom:8px}.rsu-page-heading{display:flex;align-items:end;justify-content:space-between;max-width:1200px;margin:auto}.rsu-page-heading h1{font-size:25px;line-height:1;font-weight:700;color:#14261e;margin:6px 0 0;letter-spacing:-.7px}.rsu-eyebrow,.rsu-kicker{color:#5f7469;font-size:10px;font-weight:800;letter-spacing:1.25px;text-transform:uppercase}.rsu-date{color:#7d8c84;font-size:12px;padding-bottom:2px}.rsu-date i{color:#1f7650;margin-right:6px}
.rsu-dashboard{max-width:1200px;margin:0 auto 28px;color:#14251e}.rsu-hero{min-height:263px;overflow:hidden;position:relative;display:flex;align-items:center;padding:38px 46px;border-radius:16px;background:linear-gradient(110deg,#f4f8f4 0%,#f8fbf9 58%,#edf5f0 100%);border:1px solid #e6eee9}.rsu-hero-copy{max-width:520px;position:relative;z-index:2}.rsu-kicker{display:flex;align-items:center;gap:9px}.rsu-kicker i{width:24px;height:2px;background:#29935e;display:inline-block}.rsu-hero h2{font-size:42px;font-weight:650;letter-spacing:-1.75px;line-height:1.04;margin:16px 0 12px}.rsu-hero p{max-width:455px;color:#5f6f68;line-height:1.55;margin:0 0 20px;font-size:14px}.rsu-primary-action{background:#176542;color:#fff!important;border-radius:8px;display:inline-flex;align-items:center;gap:12px;padding:10px 16px;font-size:12px;font-weight:700}.rsu-primary-action:hover{text-decoration:none;background:#0e4c30}
.rsu-hero-art{position:absolute;inset:0 0 0 54%;opacity:.75;overflow:hidden}.rsu-hero-art b{position:absolute;width:112px;height:112px;border:1px solid #cdded3;border-radius:50%;right:65px;top:30px}.rsu-hero-art span{position:absolute;bottom:48px;border:1px solid #cadbd0;background:rgba(245,250,246,.5)}.rsu-hero-art span:nth-of-type(1){width:85px;height:130px;right:100px}.rsu-hero-art span:nth-of-type(2){width:53px;height:85px;right:195px}.rsu-hero-art span:nth-of-type(3){width:40px;height:108px;right:260px}.rsu-hero-art:after{content:"";position:absolute;height:120px;width:125%;bottom:-70px;left:-12%;background:#dcebe1;border-radius:50% 50% 0 0;transform:rotate(-5deg)}.rsu-hero-art em{position:absolute;right:26px;bottom:16px;color:#94b9a2;font-size:56px;transform:rotate(28deg)}
.rsu-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:16px 0 26px}.rsu-metric-card{background:#fff;border:1px solid #e9eeeb;box-shadow:0 5px 16px rgba(22,50,36,.035);border-radius:12px;padding:15px 16px;display:flex;align-items:center;gap:12px;min-width:0}.rsu-icon{width:42px;height:42px;border-radius:12px;flex:0 0 42px;display:grid;place-items:center;font-size:17px}.blue{background:#edf5ff;color:#2471bc}.green{background:#edf8f0;color:#27834a}.amber{background:#fff5e3;color:#dc9211}.violet{background:#f2efff;color:#7754d6}.rsu-metric-card strong{display:block;font-size:22px;line-height:1;letter-spacing:-.6px}.rsu-metric-card p{margin:4px 0 2px;font-size:11px;color:#5c6b64;white-space:nowrap}.rsu-metric-card small{color:#52a06a;font-size:10px;white-space:nowrap}.rsu-metric-card small i{font-size:8px}
.rsu-main-grid{display:grid;grid-template-columns:minmax(0,1fr) 282px;gap:20px;align-items:stretch}.rsu-section-title{display:flex;align-items:end;justify-content:space-between;margin:0 0 11px}.rsu-section-title h3{font-size:21px;font-weight:700;letter-spacing:-.6px;margin:3px 0 0}.rsu-section-title>span{height:7px;width:7px;border-radius:50%;background:#62b87a;margin:0 3px 5px}.rsu-access-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.rsu-access-card{min-height:104px;display:flex;align-items:flex-start;gap:10px;padding:16px 13px;border-radius:11px;background:#fff;color:#172921!important;border:1px solid #e8edeb;transition:.2s}.rsu-access-card:hover{text-decoration:none;transform:translateY(-2px);box-shadow:0 9px 22px rgba(22,60,38,.08);border-color:#cbe0d2}.rsu-access-card .rsu-icon{width:35px;height:35px;flex-basis:35px;border-radius:10px;font-size:14px}.rsu-access-card b{display:block;font-size:11px;margin:2px 0 5px;line-height:1.15}.rsu-access-card small{display:block;font-size:10px;line-height:1.35;color:#7a8781}
.rsu-commitment{overflow:hidden;min-height:273px;position:relative;border-radius:14px;background:#103f2d}.rsu-commitment>img{position:absolute;width:100%;height:100%;object-fit:cover;object-position:73% center}.rsu-commitment>div{position:absolute;inset:0;background:linear-gradient(90deg,rgba(8,43,28,.97),rgba(11,61,39,.81) 43%,rgba(12,58,38,.05))}.rsu-commitment section{position:absolute;z-index:1;left:21px;right:65px;bottom:20px;color:#fff}.rsu-commitment section>span{font-size:10px;text-transform:uppercase;letter-spacing:1px;color:#a9dab9;font-weight:700}.rsu-commitment h3{font-size:22px;line-height:1.08;letter-spacing:-.7px;margin:8px 0}.rsu-commitment p{font-size:10px;line-height:1.45;margin:0 0 11px;color:#d4e7da}.rsu-commitment a{font-size:10px;font-weight:700;color:#fff!important}
.rsu-values{background:#fff;border:1px solid #e7eeea;border-radius:13px;margin-top:20px;display:grid;grid-template-columns:repeat(3,1fr);padding:18px 24px}.rsu-values>div{display:flex;gap:12px;align-items:center;padding:0 20px;border-right:1px solid #edf0ee}.rsu-values>div:first-child{padding-left:0}.rsu-values>div:last-child{border:0}.rsu-values>div>span{display:grid;place-items:center;width:37px;height:37px;border-radius:50%;background:#ecf7ef;color:#248047;font-size:17px;flex:0 0 37px}.rsu-values>div:nth-child(2)>span{background:#eff8e9;color:#52a326}.rsu-values>div:nth-child(3)>span{background:#eef5ff;color:#397ac5}.rsu-values p{margin:0}.rsu-values b{display:block;font-size:11px}.rsu-values small{display:block;color:#718078;font-size:10px;margin-top:3px;line-height:1.3}
@media(max-width:991px){.rsu-metrics{grid-template-columns:repeat(2,1fr)}.rsu-main-grid{grid-template-columns:1fr}.rsu-commitment{min-height:240px}.rsu-access-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.rsu-page-heading{display:block}.rsu-date{margin-top:10px}.rsu-hero{padding:28px 23px;min-height:280px}.rsu-hero h2{font-size:34px}.rsu-hero-art{left:35%;opacity:.38}.rsu-metrics,.rsu-access-grid{grid-template-columns:1fr}.rsu-values{grid-template-columns:1fr;gap:15px}.rsu-values>div{padding:0;border:0}.rsu-metric-card p{white-space:normal}}

/* RSU-DASHBOARD-C-V1 */

.rsu-dashboard .rsu-hero {
    min-height: 310px;
    padding: 48px 55px;
    border-radius: 20px;
    border: 1px solid #205b42;
    background: linear-gradient(115deg,#103c2c 0%,#155239 55%,#24734d 100%);
    box-shadow: 0 12px 32px rgba(16,60,44,.15);
}

.rsu-dashboard .rsu-hero-copy {
    max-width: 590px;
    z-index: 2;
}

.rsu-dashboard .rsu-kicker {
    color: #bce8c9;
    font-size: 11px;
}

.rsu-dashboard .rsu-kicker i {
    background: #80dda3;
    height: 3px;
}

.rsu-dashboard .rsu-hero h2 {
    color: #fff;
    font-size: clamp(32px,3.4vw,48px);
    font-weight: 750;
    line-height: 1.1;
    margin: 20px 0 15px;
}

.rsu-dashboard .rsu-hero p {
    color: #d2e8d9;
    font-size: 14px;
    line-height: 1.7;
}

.rsu-dashboard .rsu-primary-action {
    background: #fff;
    color: #155239 !important;
    padding: 13px 22px;
    border-radius: 9px;
    font-size: 13px;
    transition: .2s;
}

.rsu-dashboard .rsu-primary-action:hover {
    background: #e1f5e7;
    color: #103c2c !important;
    transform: translateY(-2px);
}

.rsu-dashboard .rsu-hero-art {
    inset: 0 0 0 62%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
    opacity: 1;
    pointer-events: none;
}

.rsu-dashboard .rsu-hero-art::after {
    display: none;
}

.rsu-eco-circle {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 20px rgba(255,255,255,.035);
}

.rsu-eco-circle i {
    font-size: 78px;
    color: #b4e7c4;
}

.rsu-eco-text {
    color: #c6e7d1;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
    text-align: center;
}

@media(max-width:991px) {
    .rsu-dashboard .rsu-hero {
        padding: 40px 32px;
    }

    .rsu-dashboard .rsu-hero-art {
        left: 65%;
        opacity: .28;
    }
}

@media(max-width:575px) {
    .rsu-dashboard .rsu-hero {
        min-height: 320px;
        padding: 32px 24px;
    }

    .rsu-dashboard .rsu-hero h2 {
        font-size: 33px;
    }

    .rsu-dashboard .rsu-hero-art {
        left: 45%;
        opacity: .15;
    }

    .rsu-eco-circle {
        width: 140px;
        height: 140px;
    }

    .rsu-eco-circle i {
        font-size: 60px;
    }
}


/* RSU - Banner sin ilustracion */
.rsu-dashboard .rsu-hero-copy {
    max-width: 780px;
}

.rsu-dashboard .rsu-hero {
    min-height: 270px;
}


/* RSU - Accesos sin tarjeta lateral */
.rsu-dashboard .rsu-main-grid {
    grid-template-columns: 1fr;
}

.rsu-dashboard .rsu-access-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 767px) {
    .rsu-dashboard .rsu-access-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575px) {
    .rsu-dashboard .rsu-access-grid {
        grid-template-columns: 1fr;
    }
}


/* RSU - Reciclaje sin rectangulo ni etiqueta */
.rsu-dashboard .rsu-eco-circle {
    background: transparent;
    border: none;
    box-shadow: none;
}

.rsu-dashboard .rsu-hero-art {
    gap: 0;
}


/* RSU - Simbolo ambiental visible */
.rsu-dashboard .rsu-hero-art {
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 1;
    pointer-events: none;
}

.rsu-dashboard .rsu-hero-art::after {
    display: none;
}

.rsu-dashboard .rsu-eco-circle {
    width: 180px;
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.18);
    box-shadow: 0 0 0 20px rgba(255,255,255,.035);
}

.rsu-dashboard .rsu-eco-circle i {
    color: #b4e7c4;
    font-size: 78px;
}


/* ==========================================
   RSU-METRICAS-DISENO-C
   Tarjetas estadisticas modernas
========================================== */

.rsu-dashboard .rsu-metrics {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin: 20px 0 30px;
}

.rsu-dashboard .rsu-metric-card {
    min-height: 145px;
    padding: 22px 20px;
    border-radius: 17px;
    border: 1px solid transparent;
    box-shadow: 0 6px 18px rgba(25, 60, 40, .05);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    transition: transform .2s, box-shadow .2s;
}

.rsu-dashboard .rsu-metric-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 25px rgba(25, 60, 40, .10);
}

/* Colores individuales */

.rsu-dashboard .rsu-metric-card:nth-child(1) {
    background: #e7f1ff;
    border-color: #d2e5fb;
}

.rsu-dashboard .rsu-metric-card:nth-child(2) {
    background: #e5f6ec;
    border-color: #ccebd9;
}

.rsu-dashboard .rsu-metric-card:nth-child(3) {
    background: #fff1dc;
    border-color: #f5dfbd;
}

.rsu-dashboard .rsu-metric-card:nth-child(4) {
    background: #f0eafe;
    border-color: #e1d6fa;
}

/* Iconos */

.rsu-dashboard .rsu-metric-card .rsu-icon {
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    border-radius: 13px;
    background: rgba(255, 255, 255, .85);
    font-size: 20px;
    display: grid;
    place-items: center;
}

/* Cifras */

.rsu-dashboard .rsu-metric-card strong {
    font-size: 32px;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -1px;
    color: #20362b;
}

/* Etiquetas */

.rsu-dashboard .rsu-metric-card p {
    color: #506458;
    font-size: 12px;
    font-weight: 650;
    margin: 6px 0 3px;
    white-space: normal;
}

.rsu-dashboard .rsu-metric-card small {
    color: #648171;
    font-size: 10px;
    white-space: normal;
}

/* Pantallas medianas */

@media (max-width: 991px) {
    .rsu-dashboard .rsu-metrics {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Celulares */

@media (max-width: 575px) {
    .rsu-dashboard .rsu-metrics {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .rsu-dashboard .rsu-metric-card {
        min-height: 125px;
        padding: 18px;
    }
}


/* RSU-BANNER-COMPACTO-V1 */

.rsu-dashboard .rsu-hero {
    min-height: 210px;
    padding: 26px 38px;
}

.rsu-dashboard .rsu-hero h2 {
    font-size: clamp(27px, 2.7vw, 36px);
    line-height: 1.12;
    margin: 12px 0 10px;
}

.rsu-dashboard .rsu-hero p {
    font-size: 12px;
    line-height: 1.5;
    margin-bottom: 14px;
}

.rsu-dashboard .rsu-primary-action {
    padding: 9px 16px;
    font-size: 12px;
}

.rsu-dashboard .rsu-eco-circle {
    width: 125px;
    height: 125px;
    box-shadow: 0 0 0 12px rgba(255,255,255,.035);
}

.rsu-dashboard .rsu-eco-circle i {
    font-size: 57px;
}

@media (max-width: 575px) {
    .rsu-dashboard .rsu-hero {
        min-height: 215px;
        padding: 25px 22px;
    }

    .rsu-dashboard .rsu-hero h2 {
        font-size: 28px;
    }

    .rsu-dashboard .rsu-eco-circle {
        width: 100px;
        height: 100px;
    }

    .rsu-dashboard .rsu-eco-circle i {
        font-size: 45px;
    }
}


/* RSU-METRICAS-COMPACTAS-V1 */

.rsu-dashboard .rsu-metrics {
    gap: 12px;
    margin: 14px 0 22px;
}

.rsu-dashboard .rsu-metric-card {
    min-height: 92px;
    padding: 14px 16px;
    border-radius: 13px;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: flex-start;
    gap: 12px;
}

.rsu-dashboard .rsu-metric-card .rsu-icon {
    width: 40px;
    height: 40px;
    flex: 0 0 40px;
    border-radius: 11px;
    font-size: 17px;
}

.rsu-dashboard .rsu-metric-card strong {
    font-size: 25px;
    letter-spacing: -0.6px;
}

.rsu-dashboard .rsu-metric-card p {
    font-size: 11px;
    margin: 3px 0;
    line-height: 1.3;
}

.rsu-dashboard .rsu-metric-card small {
    font-size: 10px;
}

@media (max-width: 575px) {
    .rsu-dashboard .rsu-metric-card {
        min-height: 85px;
        padding: 12px 14px;
    }
}


/* RSU-ACCESOS-RAPIDOS-V1 */

.rsu-dashboard .rsu-access-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.rsu-dashboard .rsu-access-card {
    min-height: 95px;
    padding: 18px 15px;
    border-radius: 13px;
    border: 1px solid #dce9e0;
    background: linear-gradient(145deg, #ffffff, #f2f8f4);
    display: flex;
    align-items: center;
    gap: 13px;
    transition: all .2s ease;
}

.rsu-dashboard .rsu-access-card:hover {
    transform: translateY(-3px);
    background: #eaf6ee;
    border-color: #88c7a0;
    box-shadow: 0 8px 20px rgba(17, 74, 43, .10);
}

.rsu-dashboard .rsu-access-card .rsu-icon {
    width: 43px;
    height: 43px;
    flex: 0 0 43px;
    border-radius: 12px;
    font-size: 19px;
}

.rsu-dashboard .rsu-access-card b {
    font-size: 12px;
    font-weight: 750;
    margin-bottom: 5px;
}

.rsu-dashboard .rsu-access-card small {
    font-size: 10px;
    line-height: 1.5;
}

@media (max-width: 991px) {
    .rsu-dashboard .rsu-access-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575px) {
    .rsu-dashboard .rsu-access-grid {
        grid-template-columns: 1fr;
    }
}


/* ==========================================
   RSU-DASHBOARD-MODULOS-FOTO-V2
========================================== */

/* Distribucion principal */

.rsu-dashboard .rsu-main-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 310px;
    align-items: stretch;
    gap: 18px;
}

/* Tarjetas de modulos */

.rsu-dashboard .rsu-access-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.rsu-dashboard .rsu-access-card {
    min-height: 105px;
    padding: 16px 14px;
    border-radius: 14px;
    background: #ffffff;
    border: 1px solid #dfece4;
    border-bottom: 3px solid #d1e9d9;
    align-items: flex-start;
    gap: 11px;
    box-shadow: 0 3px 12px rgba(17, 70, 42, .04);
    transition: .2s ease;
}

.rsu-dashboard .rsu-access-card:hover {
    background: #f0f9f3;
    border-color: #93cbaa;
    border-bottom-color: #238252;
    transform: translateY(-3px);
    box-shadow: 0 9px 22px rgba(17, 70, 42, .1);
}

.rsu-dashboard .rsu-access-card .rsu-icon {
    width: 39px;
    height: 39px;
    flex: 0 0 39px;
    font-size: 17px;
    border-radius: 11px;
}

.rsu-dashboard .rsu-access-card b {
    font-size: 12px;
    line-height: 1.3;
    color: #183e2a;
    margin: 2px 0 6px;
}

.rsu-dashboard .rsu-access-card small {
    font-size: 10px;
    color: #6d8175;
    line-height: 1.45;
}

/* Tarjeta con fotografia */

.rsu-dashboard .rsu-commitment {
    position: relative;
    min-height: 270px;
    border-radius: 17px;
    overflow: hidden;
    background: #164c35;
    box-shadow: 0 9px 25px rgba(15, 60, 38, .12);
    isolation: isolate;
}

.rsu-dashboard .rsu-commitment > img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: transform .4s ease;
}

.rsu-dashboard .rsu-commitment:hover > img {
    transform: scale(1.05);
}

/* Oscurecer imagen para mejorar lectura */

.rsu-dashboard .rsu-commitment > div {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        0deg,
        rgba(8, 40, 27, .96) 0%,
        rgba(12, 58, 38, .75) 42%,
        rgba(12, 58, 38, .12) 100%
    );
}

/* Contenido sobre la imagen */

.rsu-dashboard .rsu-commitment section {
    position: absolute;
    z-index: 2;
    left: 22px;
    right: 22px;
    bottom: 21px;
    color: #ffffff;
}

.rsu-dashboard .rsu-commitment section > span {
    display: inline-block;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 1.2px;
    text-transform: uppercase;
    color: #b5efc8;
    margin-bottom: 8px;
}

.rsu-dashboard .rsu-commitment h3 {
    font-size: 23px;
    font-weight: 800;
    line-height: 1.12;
    letter-spacing: -.6px;
    color: #ffffff;
    margin: 0 0 10px;
}

.rsu-dashboard .rsu-commitment p {
    color: #e0f0e5;
    font-size: 11px;
    line-height: 1.5;
    margin-bottom: 10px;
}

.rsu-dashboard .rsu-commitment a {
    color: #ffffff !important;
    font-size: 11px;
    font-weight: 700;
    text-decoration: none;
}

.rsu-dashboard .rsu-commitment a:hover {
    color: #b5efc8 !important;
}

/* Adaptacion a pantallas */

@media (max-width: 1100px) {
    .rsu-dashboard .rsu-main-grid {
        grid-template-columns: 1fr;
    }

    .rsu-dashboard .rsu-commitment {
        min-height: 240px;
    }
}

@media (max-width: 767px) {
    .rsu-dashboard .rsu-access-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 575px) {
    .rsu-dashboard .rsu-access-grid {
        grid-template-columns: 1fr;
    }

    .rsu-dashboard .rsu-commitment {
        min-height: 260px;
    }
}


/* ==========================================
   RSU-INSTITUCIONAL-V1
========================================== */

.rsu-dashboard .rsu-main-grid {
    display: block;
    margin-top: 8px;
}

.rsu-dashboard .rsu-institutional {
    background: #fff;
    border: 1px solid #dce9e1;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 6px 22px rgba(23, 68, 43, .05);
}

.rsu-dashboard .rsu-institutional-header {
    background: linear-gradient(90deg, #145438, #247b52);
    color: #fff;
    min-height: 43px;
    display: flex;
    align-items: center;
    gap: 11px;
    padding: 10px 20px;
    font-size: 13px;
    font-weight: 750;
}

.rsu-dashboard .rsu-institutional-header > i {
    font-size: 16px;
    color: #b8eed0;
}

.rsu-dashboard .rsu-institutional-tag {
    margin-left: auto;
    padding: 5px 10px;
    border-radius: 6px;
    background: rgba(255,255,255,.13);
    font-size: 9px;
    letter-spacing: .9px;
    white-space: nowrap;
}

.rsu-dashboard .rsu-institutional-body {
    display: grid;
    grid-template-columns: minmax(0, 36%) minmax(0, 1fr);
    gap: 22px;
    padding: 19px;
    background: #f7faf8;
}

.rsu-dashboard .rsu-institutional-photo {
    min-height: 295px;
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    background: #dcece2;
}

.rsu-dashboard .rsu-institutional-photo > img {
    display: block;
    width: 100%;
    height: 100%;
    position: absolute;
    inset: 0;
    object-fit: cover;
    object-position: center;
}

.rsu-dashboard .rsu-photo-placeholder {
    height: 100%;
    min-height: 295px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    color: #26714b;
}

.rsu-dashboard .rsu-photo-placeholder i {
    font-size: 60px;
}

.rsu-dashboard .rsu-photo-caption {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 22px 14px 13px;
    background: linear-gradient(transparent, rgba(7,48,28,.88));
    color: #fff;
    font-size: 12px;
    font-weight: 700;
}

.rsu-dashboard .rsu-photo-caption i {
    margin-right: 6px;
    color: #b5efc6;
}

.rsu-dashboard .rsu-institutional-info {
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.rsu-dashboard .rsu-info-kicker {
    font-size: 10px;
    font-weight: 800;
    color: #268553;
    letter-spacing: 1.1px;
}

.rsu-dashboard .rsu-info-top h3 {
    font-size: 21px;
    font-weight: 800;
    line-height: 1.2;
    color: #16472f;
    margin: 7px 0 8px;
}

.rsu-dashboard .rsu-info-top p {
    font-size: 12px;
    line-height: 1.65;
    color: #61776a;
    margin: 0 0 17px;
}

.rsu-dashboard .rsu-module-title {
    font-size: 12px;
    font-weight: 800;
    color: #244c35;
    margin: 0 0 10px;
    padding-top: 11px;
    border-top: 1px solid #dce9e1;
}

.rsu-dashboard .rsu-module-title i {
    color: #2c9760;
    margin-right: 7px;
}

.rsu-dashboard .rsu-module-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}

.rsu-dashboard .rsu-module-link {
    min-height: 45px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 11px;
    border: 1px solid #e0ebe4;
    border-radius: 9px;
    background: #fff;
    color: #234f38 !important;
    text-decoration: none !important;
    font-size: 11px;
    font-weight: 700;
    transition: .2s ease;
    min-width: 0;
}

.rsu-dashboard .rsu-module-link > i:first-child {
    width: 18px;
    text-align: center;
    color: #24945d;
    font-size: 14px;
    flex-shrink: 0;
}

.rsu-dashboard a.rsu-module-link:hover {
    background: #eaf6ed;
    border-color: #8dcaa5;
    transform: translateY(-2px);
}

.rsu-dashboard .rsu-module-arrow {
    margin-left: auto;
    font-size: 10px;
}

.rsu-dashboard .rsu-module-pending {
    background: #f9fbfa;
    color: #546b5e !important;
}

.rsu-dashboard .rsu-module-pending small {
    margin-left: auto;
    font-size: 9px;
    color: #86988d;
    font-weight: 400;
}

@media (max-width: 991px) {
    .rsu-dashboard .rsu-institutional-body {
        grid-template-columns: 1fr;
    }

    .rsu-dashboard .rsu-institutional-photo {
        min-height: 220px;
    }
}

@media (max-width: 575px) {
    .rsu-dashboard .rsu-institutional-header {
        padding: 11px 14px;
        font-size: 11px;
    }

    .rsu-dashboard .rsu-institutional-tag {
        display: none;
    }

    .rsu-dashboard .rsu-institutional-body {
        padding: 12px;
        gap: 16px;
    }

    .rsu-dashboard .rsu-module-list {
        grid-template-columns: 1fr;
    }

    .rsu-dashboard .rsu-info-top h3 {
        font-size: 19px;
    }
}

</style>
@stop
