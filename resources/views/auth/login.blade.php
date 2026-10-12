<x-guest-layout>
    <main class="login-page">
        <div class="login-container">
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

            <section class="login-form-section">
                <div class="login-form-container">
                    <img src="{{ asset('storage/logos/lg.png') }}" class="login-logo" alt="Logo del sistema">
                    <p class="login-kicker centro">Acceso al sistema</p>
                    <div class="cent">
                        <h2 class="blue">Bienvenido de nuevo</h2>
                    </div>
                    <p class="login-description centro">
                        Por favor, ingrese sus credenciales para acceder al sistema administrativo.
                    </p>

                    <x-validation-errors class="mb-3" />

                    @session('status')
                        <div class="alert alert-success">{{ $value }}</div>
                    @endsession

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Correo</label>
                            <div class="login-input">
                                <span class="input-group-text">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email') }}"
                                    class="form-control"
                                    placeholder="nombre.apellido@jlo.com"
                                    autocomplete="username"
                                    required
                                    autofocus
                                >
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="login-input">
                                <span class="input-group-text">
                                    <i class="bi bi-lock"></i>
                                </span>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    class="form-control"
                                    placeholder="••••••••"
                                    autocomplete="current-password"
                                    required
                                >
                                <button type="button" class="input-group-text login-eye" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="text-end mb-3">
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="forgot-password">
                                    ¿Olvidó su contraseña?
                                </a>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary login-button">
                            Iniciar Sesión
                            <i class="bi bi-arrow-right ms-2"></i>
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <style>
        .login-page {
            min-height: 100vh;
            background: #000;
        }

        .login-container {
            display: grid;
            grid-template-columns: 50% 50%;
            width: 100%;
            max-width: 1600px;
            min-height: 100vh;
            margin: auto;
            overflow: hidden;
            background: #eff3f8;
        }

        .login-banner {
            position: relative;
            display: flex;
            min-height: 600px;
            background-image:
                linear-gradient(to bottom, rgba(12, 25, 42, .12), rgba(8, 17, 29, .78)),
                url('/storage/logos/91a3d343-e34e-41bc-97fb-f8c5d6790e23.jpg');
            background-size: cover;
            background-position: center;
            color: white;
        }

        .login-banner-content {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            width: 100%;
            padding: 40px 36px 24px;
        }

        .login-banner h1 {
            max-width: 660px;
            margin: 0;
            color: #fff;
            font-size: clamp(32px, 3.2vw, 52px);
            font-weight: 800;
            line-height: 1.04;
            letter-spacing: -1.7px;
            text-shadow: 0 6px 20px rgba(0,0,0,.25);
        }

        .login-banner p {
            max-width: 660px;
            margin-top: 22px;
            margin-bottom: 0;
            color: rgba(255,255,255,.9);
            font-size: 15px;
            font-weight: 500;
            line-height: 1.7;
        }

        .login-banner footer {
            margin-top: 42px;
            padding-top: 26px;
            border-top: 1px solid rgba(255,255,255,.35);
            color: rgba(255,255,255,.72);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .login-form-section {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 52px 38px;
            background: #fff;

        }

        .login-form-container {
            width: 100%;
            max-width: 470px;
            padding: 34px 32px 30px;
            background: #fff;
            border: 0;
            border-radius: 0;

        }
        .centro{
            text-align: center
        }

        .login-kicker {
            margin: 0 0 7px;
            color: #607086;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .login-logo {
            display: block;
            width: auto;
            max-width: 170px;
            height: 58px;
            margin: 0 auto 22px;
            object-fit: contain;
        }

        .login-form-container h2 {
            margin: 0 0 10px;
            color: #1a2432;
            font-size: clamp(1.9rem, 2vw, 2.35rem);
            font-weight: 700;
            letter-spacing: -.045em;
            text-align: left;
        }

        .blue{
            color: #0e6bb8 !important;
        }
        .login-description {
            margin-bottom: 26px;
            color: #5d6c7f;
            font-size: 14px;
            line-height: 1.55;
        }

        .login-form-container .form-label {
            margin-bottom: 8px;
            color: #1c2b3b;
            font-size: 15px;
            font-weight: 600;
        }

        .login-input {
            display: flex;
            align-items: center;
            min-height: 52px;
            border: 1px solid #d9e0e7;
            border-radius: 8px;
            background: #fff;
            cursor: text;
            transition: border-color .15s ease;
        }

        .login-input:focus-within {
            border-color: #0d6efd;
            box-shadow: none;
        }

        .login-input .input-group-text {
            width: 46px;
            padding: 0;
            border: 0;
            background: transparent;
            color: #8698b0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-input .input-group-text i {
            font-size: 18px;
        }

        .cent{
            display: flex;
            justify-content: center;

        }
        .login-input .form-control {
            flex: 1 1 auto;
            width: 100% !important;
            min-width: 0;
            height: 51px;
            border: 0;
            outline: 0;
            background: transparent !important;
            box-shadow: none;
            color: #233246;
            font-size: 15px;
            padding-left: 0;
            padding-right: 14px;
        }

        .login-input .form-control::placeholder {
            color: #7a8ca5;
            opacity: 1;
        }

        .login-eye {
            width: 46px;
            cursor: pointer;
            border: 0;
            background: transparent;
            color: #6d7e96;
            transition: color .2s ease;
        }

        .login-eye:hover {
            color: #1f2f45;
        }

        .forgot-password {
            color: #0d6efd;
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-password:hover {
            color: #0a58ca;
            text-decoration: underline;
        }

        .login-button {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 50px;
            margin-top: 14px;
            border: 1px solid #0e6bb8;
            border-radius: 8px;
            background: #0e6bb8;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: .01em;
            box-shadow: none;
            transition: background .15s ease, color .15s ease;
        }

        .login-button:hover {
            border-color: #0b5ed7;
            background: #0b5ed7;
            color: #fff;
        }

        @media (max-width: 991.98px) {
            .login-page {
                padding: 0;
            }

            .login-container {
                grid-template-columns: 1fr;
                min-height: 100vh;
            }

            .login-banner {
                min-height: 380px;
            }

            .login-banner-content {
                padding: 35px 25px 20px;
            }

            .login-banner h1 {
                font-size: 36px;
            }

            .login-banner footer {
                margin-top: 25px;
                padding-top: 20px;
            }

            .login-form-section {
                padding: 45px 25px;
            }
        }

        @media (max-width: 480px) {
            .login-banner {
                min-height: 340px;
            }

            .login-banner h1 {
                font-size: 30px;
            }

            .login-banner p {
                font-size: 13px;
            }

            .login-form-container h2 {
                font-size: 27px;
            }
        }
    </style>

    <script>
        @if ($errors->has('email'))
            window.addEventListener('DOMContentLoaded', function () {
                window.alert('No se pudo iniciar sesión. Verifica que el correo y la contraseña sean correctos.');
            });
        @endif

        document.getElementById('togglePassword')?.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            const mostrar = input.type === 'password';

            input.type = mostrar ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !mostrar);
            icon.classList.toggle('bi-eye-slash', mostrar);
        });

        document.querySelectorAll('.login-input').forEach(function (field) {
            field.addEventListener('click', function (event) {
                if (!event.target.closest('.login-eye')) {
                    field.querySelector('.form-control')?.focus();
                }
            });
        });
    </script>
</x-guest-layout>
