<x-guest-layout>
    <style>
        html, body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: "Figtree", "Segoe UI", sans-serif;
            background: #f3f6fa;
        }

        .access-page,
        .access-page * {
            box-sizing: border-box;
        }

        .access-page {
            position: relative;
            display: flex;
            align-items: center;
            min-height: 100vh;
            min-height: 100svh;
            overflow: hidden;
            padding: 34px 18px;
            background:
                radial-gradient(circle at 8% 10%, rgba(16, 142, 154, .10), transparent 27rem),
                radial-gradient(circle at 95% 90%, rgba(44, 104, 180, .09), transparent 30rem),
                #f3f6fa;
        }

        .access-page::before,
        .access-page::after {
            position: absolute;
            z-index: 0;
            content: "";
            border: 1px solid rgba(35, 70, 100, .07);
            border-radius: 50%;
            pointer-events: none;
        }

        .access-page::before {
            top: -270px;
            right: -130px;
            width: 510px;
            height: 510px;
        }

        .access-page::after {
            bottom: -310px;
            left: -160px;
            width: 520px;
            height: 520px;
        }

        .access-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1080px;
            margin: auto;
        }

        .access-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .access-brand-mark {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            color: #fff;
            background: #173651;
            box-shadow: 0 7px 18px rgba(23, 54, 81, .16);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .access-brand-title {
            margin: 0;
            color: #1d344c;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .08em;
        }

        .access-brand-subtitle {
            margin: 4px 0 0;
            color: #8291a4;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .15em;
            text-transform: uppercase;
        }

        .access-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 13px;
            border: 1px solid #e4eaf0;
            border-radius: 50rem;
            color: #63748a;
            background: rgba(255, 255, 255, .88);
            font-size: 11px;
            font-weight: 700;
        }

        .access-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #e4a33b;
            box-shadow: 0 0 0 3px rgba(228, 163, 59, .14);
        }

        .access-card {
            overflow: hidden;
            border: 1px solid #dfe7ef;
            border-radius: 24px;
            background: #fff;
            box-shadow: 0 26px 70px rgba(26, 48, 72, .13);
        }

        .access-visual {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 540px;
            overflow: hidden;
            padding: 34px;
            color: #fff;
            background: linear-gradient(145deg, #102b46 0%, #123b55 58%, #145568 100%);
        }

        .access-visual::before {
            position: absolute;
            inset: 0;
            content: "";
            opacity: .48;
            background-image:
                linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 28px 28px;
            mask-image: linear-gradient(to bottom, #000, transparent 90%);
            pointer-events: none;
        }

        .access-visual::after {
            position: absolute;
            top: 80px;
            right: -125px;
            width: 330px;
            height: 330px;
            content: "";
            border-radius: 50%;
            background: radial-gradient(circle, rgba(45, 212, 191, .24), rgba(45, 212, 191, .04) 52%, transparent 72%);
            pointer-events: none;
        }

        .access-visual-top,
        .access-visual-center,
        .access-visual-footer {
            position: relative;
            z-index: 1;
        }

        .access-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 11px;
            border: 1px solid rgba(255,255,255,.17);
            border-radius: 50rem;
            color: #c8f5f1;
            background: rgba(255,255,255,.08);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .access-emblem {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 142px;
            height: 142px;
            margin: 0 auto 27px;
            border: 1px solid rgba(255,255,255,.20);
            border-radius: 50%;
            background: rgba(255,255,255,.07);
            box-shadow: 0 0 0 12px rgba(255,255,255,.025), 0 0 65px rgba(45,212,191,.12);
        }

        .access-emblem-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 94px;
            height: 94px;
            border-radius: 25px;
            color: #102f43;
            background: linear-gradient(145deg, #a5f3e8, #2dd4bf);
            box-shadow: 0 14px 28px rgba(0,0,0,.20);
            font-size: 48px;
        }

        .access-visual-label {
            margin: 0 0 10px;
            color: #83e8df;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .2em;
            text-transform: uppercase;
        }

        .access-visual-title {
            max-width: 340px;
            margin: 0 auto;
            color: #fff;
            font-size: clamp(25px, 2.4vw, 32px);
            font-weight: 800;
            line-height: 1.17;
            letter-spacing: -.7px;
        }

        .access-visual-description {
            max-width: 340px;
            margin: 15px auto 0;
            color: rgba(225, 240, 250, .77);
            font-size: 13px;
            line-height: 1.75;
        }

        .access-visual-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 18px;
            border-top: 1px solid rgba(255,255,255,.13);
            color: rgba(225, 240, 250, .58);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .access-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 540px;
            padding: 54px 52px;
        }

        .access-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-start;
            padding: 8px 12px;
            border: 1px solid #f4dfb6;
            border-radius: 50rem;
            color: #95600e;
            background: #fff8e9;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .access-title {
            margin: 22px 0 0;
            color: #172d44;
            font-size: clamp(29px, 3vw, 39px);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -1.2px;
        }

        .access-description {
            margin: 18px 0 0;
            color: #65758a;
            font-size: 14px;
            line-height: 1.8;
        }

        .access-help {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-top: 28px;
            padding: 18px;
            border: 1px solid #dcecf8;
            border-radius: 15px;
            background: #f3f9fd;
        }

        .access-help-icon {
            display: flex;
            flex: 0 0 38px;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 11px;
            color: #176b9a;
            background: #fff;
            box-shadow: 0 3px 10px rgba(23, 107, 154, .08);
            font-size: 17px;
        }

        .access-help-title {
            margin: 1px 0 5px;
            color: #193750;
            font-size: 13px;
            font-weight: 800;
        }

        .access-help-text {
            margin: 0;
            color: #5e7185;
            font-size: 12px;
            line-height: 1.7;
        }

        .access-logout {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 49px;
            margin-top: 30px;
            padding: 0 24px;
            border: 0;
            border-radius: 11px;
            color: #fff;
            background: linear-gradient(100deg, #087f75, #0f766e);
            box-shadow: 0 9px 20px rgba(15, 118, 110, .18);
            font-size: 13px;
            font-weight: 800;
            transition: background .2s ease, box-shadow .2s ease, transform .2s ease;
        }

        .access-logout:hover {
            color: #fff;
            background: linear-gradient(100deg, #066b64, #115e59);
            box-shadow: 0 12px 24px rgba(15, 118, 110, .23);
            transform: translateY(-1px);
        }

        .access-logout:focus-visible {
            outline: 3px solid rgba(13, 148, 136, .28);
            outline-offset: 3px;
        }

        .access-note {
            margin: 25px 0 0;
            padding-top: 17px;
            border-top: 1px solid #edf1f5;
            color: #92a0af;
            font-size: 11px;
            line-height: 1.7;
        }

        .access-page-footer {
            margin: 17px 0 0;
            color: #9aa7b5;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .14em;
            text-align: center;
        }

        @media (max-width: 991.98px) {
            .access-visual {
                min-height: 360px;
                padding: 27px;
            }

            .access-content {
                min-height: auto;
                padding: 40px;
            }

            .access-visual-center {
                padding: 30px 0;
            }

            .access-emblem {
                width: 112px;
                height: 112px;
                margin-bottom: 20px;
            }

            .access-emblem-inner {
                width: 76px;
                height: 76px;
                border-radius: 21px;
                font-size: 39px;
            }
        }

        @media (max-width: 575.98px) {
            .access-page {
                align-items: flex-start;
                padding: 20px 12px;
            }

            .access-topbar {
                margin-bottom: 14px;
            }

            .access-status {
                padding: 8px 10px;
                font-size: 10px;
            }

            .access-card {
                border-radius: 18px;
            }

            .access-visual {
                min-height: 310px;
                padding: 22px;
            }

            .access-visual-center {
                padding: 22px 0;
            }

            .access-visual-title {
                font-size: 24px;
            }

            .access-content {
                padding: 30px 24px;
            }

            .access-title {
                font-size: 30px;
            }

            .access-description {
                font-size: 13px;
            }

            .access-logout {
                width: 100%;
            }
        }
    </style>

    <main class="access-page">
        <div class="access-wrapper">
            <header class="access-topbar">
                <div class="access-brand">
                    <div class="access-brand-mark">RSU</div>
                    <div>
                        <p class="access-brand-title">SISTEMA DE GESTIÓN</p>
                        <p class="access-brand-subtitle">Portal institucional</p>
                    </div>
                </div>

                <div class="access-status">
                    <span class="access-status-dot"></span>
                    Acceso pendiente
                </div>
            </header>

            <section class="access-card">
                <div class="row g-0">
                    <div class="col-lg-5">
                        <div class="access-visual h-100">
                            <div class="access-visual-top">
                                <span class="access-kicker">
                                    <i class="bi bi-shield-check"></i>
                                    Control de acceso
                                </span>
                            </div>

                            <div class="access-visual-center text-center">
                                <div class="access-emblem">
                                    <div class="access-emblem-inner">
                                        <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
                                    </div>
                                </div>
                                <p class="access-visual-label">Acceso protegido</p>
                                <h2 class="access-visual-title">Cada usuario accede solo a lo que necesita.</h2>
                                <p class="access-visual-description">
                                    Los módulos disponibles dependen de los permisos asignados a tu cuenta.
                                </p>
                            </div>

                            <div class="access-visual-footer">
                                <span>Seguridad y control de accesos</span>
                                <span>RSU</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="access-content h-100">
                            <span class="access-badge">
                                <i class="bi bi-exclamation-circle-fill"></i>
                                Sin módulos asignados
                            </span>

                            <h1 class="access-title">Tu cuenta aún no tiene acceso al sistema</h1>

                            <p class="access-description">
                                Has iniciado sesión correctamente, pero todavía no tienes módulos habilitados para trabajar en la plataforma.
                            </p>

                            <div class="access-help">
                                <div class="access-help-icon">
                                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <h3 class="access-help-title">¿Cómo puedes obtener acceso?</h3>
                                    <p class="access-help-text">
                                        Comunícate con el administrador para que revise tu tipo de usuario y te asigne los módulos y permisos correspondientes.
                                    </p>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="access-logout">
                                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                                    Cerrar sesión
                                </button>
                            </form>

                            <p class="access-note">
                                Por seguridad, el sistema no permite ingresar a módulos que no hayan sido autorizados para tu cuenta.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <p class="access-page-footer">SISTEMA DE GESTIÓN · ACCESO SEGURO</p>
        </div>
    </main>
</x-guest-layout>
