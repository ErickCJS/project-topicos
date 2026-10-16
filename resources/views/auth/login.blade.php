
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<x-guest-layout class="complete">
    <main class="login-page">
        <div class="login-container">

            {{-- Panel izquierdo --}}
            <section class="login-banner">
                <div class="login-banner-content">
                    <div>
                        <h1>Gestionando residuos<br>para un JLO más limpio.</h1>
                        <p>
                            Plataforma para la gestión, planificación y supervisión de las operaciones
                            de recolección de residuos en José Leonardo Ortiz.
                        </p>
                    </div>

                    <footer>
                        © {{ date('Y') }} USAT - UNIVERSIDAD CATÓLICA SANTO TORIBIO DE MOGROVEJO
                    </footer>
                </div>
            </section>

            {{-- Panel derecho --}}
            <section class="login-form-section">
                <div class="login-form-container">

                    <img
                        src="{{ asset('storage/logos/lg.png') }}"
                        class="login-logo"
                        alt="Logo del sistema"
                    >

                    <p class="login-kicker">Acceso al sistema</p>
                    <h2 class="login-title">Bienvenido de nuevo</h2>

                    <p class="login-description">
                        Por favor, ingrese sus credenciales para acceder al sistema administrativo.
                    </p>

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Correo electrónico --}}
                        <div class="login-field">
                            <label for="email" class="form-label">Correo electrónico</label>

                            <div class="login-input">
                                <span class="login-input-icon" aria-hidden="true">
                                    <i class="bi bi-envelope"></i>
                                </span>

                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control"
                                    placeholder="Ingrese su correo electrónico"
                                    autocomplete="username"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        {{-- Contraseña --}}
                        <div class="login-field login-password-field">
                            <label for="password" class="form-label">Contraseña</label>

                            <div class="login-input">
                                <span class="login-input-icon" aria-hidden="true">
                                    <i class="bi bi-lock"></i>
                                </span>

                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="Ingrese su contraseña"
                                    autocomplete="current-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="login-eye"
                                    id="togglePassword"
                                    aria-label="Mostrar contraseña"
                                    aria-controls="password"
                                    aria-pressed="false"
                                >
                                    <i class="bi bi-eye" id="eyeIcon" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Recuperación de contraseña --}}
                        <div class="login-forgot">
                            @if (Route::has('password.request'))
                                <a
                                    href="{{ route('password.request') }}"
                                    class="forgot-password"
                                >
                                    ¿Olvidó su contraseña?
                                </a>
                            @endif
                        </div>

                        {{-- Botón de acceso --}}
                        <button type="submit" class="login-button">
                            <span>Iniciar sesión</span>
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>

     

                </div>
            </section>

        </div>
    </main>

    @if (session('status'))
        <span id="loginStatusMessage" data-message="{{ session('status') }}" hidden></span>
    @endif

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Alertas de autenticación --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            @if ($errors->any())
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo iniciar sesión',
                    text: 'El correo electrónico o la contraseña son incorrectos. Verifique sus datos e inténtelo nuevamente.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#176542',
                    allowOutsideClick: false,
                    customClass: {
                        popup: 'login-swal-popup',
                        confirmButton: 'login-swal-button'
                    }
                });
            @elseif (session('status'))
                Swal.fire({
                    icon: 'success',
                    title: '¡Operación exitosa!',
                    text: document.getElementById('loginStatusMessage').dataset.message,
                    confirmButtonText: 'Continuar',
                    confirmButtonColor: '#176542',
                    customClass: {
                        popup: 'login-swal-popup',
                        confirmButton: 'login-swal-button'
                    }
                });
            @endif
        });
    </script>

    {{-- Mostrar y ocultar contraseña --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const boton = document.getElementById('togglePassword');
            const password = document.getElementById('password');
            const icono = document.getElementById('eyeIcon');

            if (!boton || !password || !icono) return;

            boton.addEventListener('click', function () {
                const mostrar = password.type === 'password';

                password.type = mostrar ? 'text' : 'password';

                icono.classList.toggle('bi-eye', !mostrar);
                icono.classList.toggle('bi-eye-slash', mostrar);

                boton.setAttribute(
                    'aria-label',
                    mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
                );

                boton.setAttribute('aria-pressed', String(mostrar));
            });
        });
    </script>

    <style>
        /* Configuración general */
        html,
        body {
            width: 100%;
            min-height: 100%;
            margin: 0;
            padding: 0;
        }

        /* Contenedor que agrega el layout de invitados de Laravel */
        body > .font-sans {
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
        }

        .login-page,
        .login-page *,
        .login-page *::before,
        .login-page *::after {
            box-sizing: border-box;
        }

        .login-page {
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            margin: 0;
            padding: 0;
            overflow-x: clip;
            background: #fff;
        }

        .login-container {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            width: 100%;
            min-height: 100vh;
            min-height: 100svh;
            margin: 0;
            overflow: hidden;
            background: #fff;
        }

        .login-banner,
        .login-form-section {
            min-width: 0;
        }

        /* Panel izquierdo */
        .login-banner {
            position: relative;
            display: flex;
            min-height: 600px;
            background-image:
                linear-gradient(
                    to bottom,
                    rgba(12, 25, 42, .10),
                    rgba(8, 17, 29, .80)
                ),
                url('/storage/logos/91a3d343-e34e-41bc-97fb-f8c5d6790e23.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: #fff;
        }

        .login-banner-content {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            width: 100%;
            min-width: 0;
            padding: 40px 36px 24px;
        }

        .login-banner h1 {
            max-width: 660px;
            margin: 0;
            color: #fff;
            font-size: clamp(32px, 3.2vw, 52px);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -1.5px;
            overflow-wrap: break-word;
            text-shadow: 0 6px 20px rgba(0, 0, 0, .25);
        }

        .login-banner p {
            max-width: 580px;
            margin: 22px 0 0;
            color: rgba(255, 255, 255, .92);
            font-size: 15px;
            font-weight: 500;
            line-height: 1.7;
        }

        .login-banner footer {
            margin-top: 42px;
            padding-top: 24px;
            border-top: 1px solid rgba(255, 255, 255, .35);
            color: rgba(255, 255, 255, .78);
            font-size: 10px;
            font-weight: 700;
            line-height: 1.7;
            letter-spacing: .04em;
        }

        /* Panel derecho */
        .login-form-section {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 0;
            padding: 40px;
            background: #fff;
        }

        .login-form-container {
            width: 100%;
            max-width: 440px;
            min-width: 0;
            padding: 0;
            background: #fff;
        }

        .login-logo {
            display: block;
            width: auto;
            max-width: min(170px, 100%);
            height: 58px;
            margin: 0 auto 30px;
            object-fit: contain;
        }

        .login-kicker {
            margin: 0 0 10px;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .12em;
            text-align: center;
            text-transform: uppercase;
        }

        .login-title {
            margin: 0 0 12px;
            color: #1769aa;
            font-size: clamp(27px, 2.5vw, 34px);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -.7px;
            text-align: center;
            overflow-wrap: break-word;
        }

        .login-description {
            margin: 0 0 28px;
            color: #64748b;
            font-size: 14px;
            line-height: 1.65;
            text-align: center;
        }

        /* Etiquetas */
        .login-field {
            margin-bottom: 18px;
        }

        .login-password-field {
            margin-bottom: 0;
        }

        .login-form-container .form-label {
            display: block;
            margin-bottom: 7px;
            color: #263445;
            font-size: 14px;
            font-weight: 600;
        }

        /* Inputs compactos y alineados */
        .login-input {
            display: flex;
            align-items: center;
            width: 100%;
            height: 48px;
            min-height: 48px;
            overflow: hidden;
            border: 1px solid #d4dbe4;
            border-radius: 6px;
            background: #fff;
            box-shadow: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .login-input:hover {
            border-color: #aebbc9;
        }

        .login-input:focus-within {
            border-color: #1769aa;
            box-shadow: 0 0 0 1px #1769aa;
        }

        .login-input-icon {
            display: flex;
            flex: 0 0 42px;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #758397;
        }

        .login-input-icon i {
            font-size: 16px;
            line-height: 1;
        }

        .login-input .form-control {
            display: block;
            flex: 1 1 auto;
            width: 100%;
            min-width: 0;
            height: 100%;
            margin: 0;
            padding: 0 8px 0 0;
            border: 0 !important;
            border-radius: 0;
            outline: 0;
            background: transparent !important;
            color: #263445;
            font-family: inherit;
            font-size: 14px;
            line-height: normal;
            box-shadow: none !important;
        }

        .login-input .form-control::placeholder {
            color: #929eae;
            opacity: 1;
        }

        .login-input .form-control:focus {
            border: 0 !important;
            outline: 0;
            box-shadow: none !important;
        }

        .login-input .form-control:-webkit-autofill,
        .login-input .form-control:-webkit-autofill:hover,
        .login-input .form-control:-webkit-autofill:focus {
            -webkit-text-fill-color: #263445;
            transition: background-color 9999s ease-in-out 0s;
        }

        /* Botón para ver u ocultar la contraseña */
        .login-eye {
            display: flex;
            flex: 0 0 40px;
            align-items: center;
            justify-content: center;
            align-self: stretch;
            height: 100%;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: #758397;
            font-size: 17px;
            cursor: pointer;
            transition: color .15s ease;
        }

        .login-eye:hover {
            background: transparent;
            color: #1769aa;
        }

        .login-eye:focus-visible {
            outline: 2px solid #1769aa;
            outline-offset: -4px;
        }

        /* Recuperación de contraseña */
        .login-forgot {
            display: flex;
            justify-content: flex-end;
            min-height: 38px;
            padding-top: 10px;
            margin-bottom: 14px;
        }

        .forgot-password {
            color: #1769aa;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-password:hover {
            color: #104e80;
            text-decoration: underline;
        }

        /* Botón de inicio de sesión */
        .login-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            min-height: 48px;
            margin: 0;
            padding: 12px 18px;
            border: 1px solid #1769aa;
            border-radius: 6px;
            background: #1769aa;
            color: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 600;
            box-shadow: none;
            cursor: pointer;
            transition: background-color .15s ease, border-color .15s ease;
        }

        .login-button:hover {
            border-color: #12578e;
            background: #12578e;
            color: #fff;
        }

        .login-button:active {
            background: #104e80;
            color: #fff;
        }

        .login-button:focus-visible {
            outline: 3px solid rgba(23, 105, 170, .25);
            outline-offset: 3px;
        }

        .login-button i {
            font-size: 17px;
            transition: transform .15s ease;
        }

        .login-button:hover i {
            transform: translateX(3px);
        }

        /* Información de seguridad */
        .login-security {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 28px;
            color: #8491a2;
            font-size: 11px;
            line-height: 1.6;
            text-align: center;
        }

        .login-security i {
            flex-shrink: 0;
            color: #1769aa;
            font-size: 14px;
        }

        /* SweetAlert2 */
        .login-swal-popup {
            width: min(420px, calc(100% - 32px)) !important;
            border-radius: 8px !important;
            padding: 24px !important;
        }

        .login-swal-button {
            min-width: 0;
            padding: 8px 14px !important;
            border-radius: 6px !important;
            font-weight: 600 !important;
        }

        /* Tablets */
        @media (max-width: 991.98px) {
            .login-container {
                grid-template-columns: minmax(0, 1fr);
            }

            .login-banner {
                min-height: 360px;
            }

            .login-banner-content {
                padding: 32px 28px 22px;
            }

            .login-banner h1 {
                font-size: clamp(28px, 5vw, 38px);
                letter-spacing: -.8px;
            }

            .login-banner p {
                max-width: 600px;
                font-size: 14px;
            }

            .login-banner footer {
                margin-top: 25px;
                padding-top: 18px;
            }

            .login-form-section {
                padding: 42px 28px;
            }

            .login-form-container {
                max-width: 440px;
            }

            .login-logo {
                margin-bottom: 24px;
            }
        }

        /* Celulares */
        @media (max-width: 480px) {
            .login-banner {
                min-height: 320px;
            }

            .login-banner-content {
                padding: 28px 22px 18px;
            }

            .login-banner h1 {
                font-size: 28px;
                line-height: 1.12;
            }

            .login-banner p {
                margin-top: 15px;
                font-size: 13px;
                line-height: 1.6;
            }

            .login-banner footer {
                margin-top: 22px;
                font-size: 9px;
            }

            .login-form-section {
                padding: 34px 22px;
            }

            .login-title {
                font-size: 27px;
            }

            .login-description {
                margin-bottom: 24px;
                font-size: 13px;
            }

            .login-field {
                margin-bottom: 16px;
            }

            .login-password-field {
                margin-bottom: 0;
            }

            .login-input {
                height: 46px;
                min-height: 46px;
            }

            .login-input-icon {
                flex-basis: 38px;
            }

            .login-eye {
                flex-basis: 38px;
            }

            .login-input .form-control {
                font-size: 14px;
            }

            .login-forgot {
                min-height: 36px;
                margin-bottom: 12px;
            }

            .login-security {
                margin-top: 24px;
                font-size: 10px;
            }
        }

        /* Accesibilidad */
        @media (prefers-reduced-motion: reduce) {
            .login-input,
            .login-eye,
            .login-button,
            .login-button i {
                transition: none;
            }
        }
    </style>
</x-guest-layout>
