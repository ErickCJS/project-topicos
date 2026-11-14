<x-guest-layout>
    <main class="restricted-page">
        <div class="restricted-layout">
            <section class="restricted-banner" aria-label="Plataforma de gestión de residuos">
                <div class="restricted-banner-content">
                    <div class="restricted-context">
                        <span class="restricted-context-line"></span>
                        <span>PLATAFORMA DE GESTIÓN OPERATIVA</span>
                    </div>

                    <div class="restricted-banner-copy">
                        <p class="restricted-overline">JOSÉ LEONARDO ORTIZ</p>
                        <h2>Gestionando residuos<br>para un JLO más limpio.</h2>
                        <p class="restricted-banner-description">
                            Herramientas para organizar, planificar y supervisar las operaciones de recolección.
                        </p>
                    </div>

                    <footer class="restricted-banner-footer">
                        © {{ date('Y') }} USAT · UNIVERSIDAD CATÓLICA SANTO TORIBIO DE MOGROVEJO
                    </footer>
                </div>
            </section>

            <section class="restricted-panel">
                <div class="restricted-content">
                    <div class="restricted-logo-wrap">
                        <img
                            src="{{ asset('storage/logos/lg.png') }}"
                            class="restricted-logo"
                            alt="Logo del sistema"
                        >
                    </div>

                    <div class="restricted-heading">
                        <div class="restricted-eyebrow">
                            <span class="restricted-status-dot"></span>
                            ESTADO DE LA CUENTA
                        </div>

                        <h1>No tienes módulos asignados</h1>
                        <p class="restricted-intro">
                            Tu inicio de sesión fue correcto, pero tu cuenta todavía no tiene permisos para ingresar a las opciones del sistema.
                        </p>
                    </div>

                    <div class="restricted-separator"></div>

                    <div class="restricted-next-step">
                        <div class="restricted-next-icon">
                            <i class="bi bi-person-lock" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3>¿Necesitas acceso?</h3>
                            <p>
                                Comunícate con el administrador del sistema. Él podrá revisar tu tipo de usuario y habilitar los módulos que corresponden a tus funciones.
                            </p>
                        </div>
                    </div>

                    <div class="restricted-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        <span>Hasta que se asignen los permisos, no podrás acceder a las funciones de la plataforma.</span>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="restricted-logout-form">
                        @csrf
                        <button type="submit" class="restricted-logout">
                            <span>Cerrar sesión</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

                    <p class="restricted-support">
                        ¿Crees que esto es un error? Solicita al administrador que verifique tu asignación.
                    </p>
                </div>

                <footer class="restricted-panel-footer">
                    <span>ACCESO Y SEGURIDAD</span>
                    <span class="restricted-footer-mark">RSU</span>
                </footer>
            </section>
        </div>
    </main>

    <style>
        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
        }

        body {
            font-family: "Figtree", "Segoe UI", sans-serif;
            background: #fff;
        }

        .restricted-page,
        .restricted-page * {
            box-sizing: border-box;
        }

        .restricted-page {
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            background: #fff;
        }

        .restricted-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            overflow: hidden;
        }

        /* Mantiene la identidad visual de la pantalla de inicio de sesión. */
        .restricted-banner {
            position: relative;
            display: flex;
            min-width: 0;
            min-height: 650px;
            color: #fff;
            background-image:
                linear-gradient(to bottom, rgba(12, 25, 42, .08), rgba(8, 17, 29, .82)),
                url('/storage/logos/91a3d343-e34e-41bc-97fb-f8c5d6790e23.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .restricted-banner-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
            min-width: 0;
            min-height: 100%;
            padding: 34px 38px 25px;
        }

        .restricted-context {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: rgba(255, 255, 255, .88);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .13em;
        }

        .restricted-context-line {
            display: inline-block;
            width: 25px;
            height: 2px;
            background: #58b7a0;
        }

        .restricted-banner-copy {
            max-width: 620px;
            margin-top: auto;
            padding-bottom: 35px;
        }

        .restricted-overline {
            margin: 0 0 14px;
            color: #b8e2d7;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .16em;
        }

        .restricted-banner-copy h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(34px, 3.6vw, 54px);
            font-weight: 800;
            line-height: 1.07;
            letter-spacing: -1.7px;
            text-shadow: 0 4px 18px rgba(0, 0, 0, .20);
        }

        .restricted-banner-description {
            max-width: 480px;
            margin: 19px 0 0;
            color: rgba(255, 255, 255, .88);
            font-size: 14px;
            line-height: 1.75;
        }

        .restricted-banner-footer {
            padding-top: 19px;
            border-top: 1px solid rgba(255, 255, 255, .35);
            color: rgba(255, 255, 255, .76);
            font-size: 9px;
            font-weight: 700;
            line-height: 1.7;
            letter-spacing: .045em;
        }

        .restricted-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            min-height: 650px;
            padding: 48px clamp(35px, 5vw, 78px) 28px;
            background: #fff;
        }

        .restricted-content {
            width: 100%;
            max-width: 465px;
            margin: auto;
        }

        .restricted-logo-wrap {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 58px;
        }

        .restricted-logo {
            display: block;
            width: auto;
            max-width: 180px;
            height: 58px;
            object-fit: contain;
            object-position: left center;
        }

        .restricted-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 17px;
            color: #8a6a31;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .13em;
        }

        .restricted-status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #d59a36;
            box-shadow: 0 0 0 3px #f8edda;
        }

        .restricted-heading h1 {
            max-width: 430px;
            margin: 0;
            color: #1769aa;
            font-size: clamp(29px, 2.8vw, 37px);
            font-weight: 700;
            line-height: 1.16;
            letter-spacing: -.9px;
        }

        .restricted-intro {
            max-width: 435px;
            margin: 18px 0 0;
            color: #657386;
            font-size: 14px;
            line-height: 1.8;
        }

        .restricted-separator {
            width: 100%;
            height: 1px;
            margin: 30px 0;
            background: #e8edf2;
        }

        .restricted-next-step {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .restricted-next-icon {
            display: flex;
            flex: 0 0 42px;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border: 1px solid #dce8f1;
            border-radius: 8px;
            color: #1769aa;
            background: #f5f9fc;
            font-size: 19px;
        }

        .restricted-next-step h3 {
            margin: 1px 0 7px;
            color: #26394d;
            font-size: 14px;
            font-weight: 700;
        }

        .restricted-next-step p {
            margin: 0;
            color: #68788a;
            font-size: 13px;
            line-height: 1.75;
        }

        .restricted-note {
            display: flex;
            align-items: flex-start;
            gap: 9px;
            margin-top: 25px;
            padding: 13px 15px;
            border-left: 3px solid #d7a14a;
            background: #faf8f3;
            color: #776b57;
            font-size: 11px;
            line-height: 1.65;
        }

        .restricted-note i {
            flex: 0 0 auto;
            margin-top: 1px;
            color: #aa813b;
            font-size: 14px;
        }

        .restricted-logout-form {
            margin-top: 30px;
        }

        .restricted-logout {
            display: inline-flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            min-width: 190px;
            min-height: 48px;
            padding: 0 17px;
            border: 1px solid #1769aa;
            border-radius: 6px;
            color: #fff;
            background: #1769aa;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .restricted-logout:hover {
            border-color: #12578e;
            color: #fff;
            background: #12578e;
        }

        .restricted-logout:focus-visible {
            outline: 3px solid rgba(23, 105, 170, .25);
            outline-offset: 3px;
        }

        .restricted-logout i {
            font-size: 16px;
            transition: transform .15s ease;
        }

        .restricted-logout:hover i {
            transform: translateX(3px);
        }

        .restricted-support {
            margin: 23px 0 0;
            color: #929eac;
            font-size: 11px;
            line-height: 1.65;
        }

        .restricted-panel-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            max-width: 465px;
            margin: 40px auto 0;
            padding-top: 17px;
            border-top: 1px solid #edf0f3;
            color: #99a4af;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .13em;
        }

        .restricted-footer-mark {
            color: #1769aa;
            font-size: 11px;
            letter-spacing: .1em;
        }

        @media (max-width: 991.98px) {
            .restricted-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            .restricted-banner {
                min-height: 390px;
            }

            .restricted-banner-content {
                padding: 28px 30px 20px;
            }

            .restricted-banner-copy {
                padding-bottom: 24px;
            }

            .restricted-banner-copy h2 {
                font-size: clamp(32px, 6vw, 43px);
            }

            .restricted-panel {
                min-height: auto;
                padding: 46px 30px 24px;
            }

            .restricted-content,
            .restricted-panel-footer {
                max-width: 560px;
            }

            .restricted-logo-wrap {
                margin-bottom: 42px;
            }
        }

        @media (max-width: 480px) {
            .restricted-banner {
                min-height: 330px;
                background-position: 58% center;
            }

            .restricted-banner-content {
                padding: 23px 21px 18px;
            }

            .restricted-banner-copy h2 {
                font-size: 31px;
                letter-spacing: -.8px;
            }

            .restricted-banner-description {
                margin-top: 13px;
                font-size: 12px;
            }

            .restricted-banner-footer {
                font-size: 8px;
            }

            .restricted-panel {
                padding: 35px 23px 22px;
            }

            .restricted-logo-wrap {
                margin-bottom: 37px;
            }

            .restricted-logo {
                height: 50px;
                max-width: 155px;
            }

            .restricted-heading h1 {
                font-size: 30px;
            }

            .restricted-intro {
                font-size: 13px;
            }

            .restricted-separator {
                margin: 24px 0;
            }

            .restricted-logout {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .restricted-logout,
            .restricted-logout i {
                transition: none;
            }
        }
    </style>
</x-guest-layout>
