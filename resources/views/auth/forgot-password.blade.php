<x-guest-layout>
    <link rel="stylesheet" href="{{ asset('css/auth-pages.css') }}">
    <main class="auth-page">
        <div class="auth-container">
            <section class="auth-banner">
                <div class="auth-banner-content">
                    <div>
                        <h1>Gestionando residuos<br>para un JLO más limpio.</h1>
                        <p>Plataforma para la gestión, planificación y supervisión de las operaciones de recolección de residuos en José Leonardo Ortiz.</p>
                    </div>
                    <footer>© {{ date('Y') }} USAT - UNIVERSIDAD CATÓLICA SANTO TORIBIO DE MOGROVEJO</footer>
                </div>
            </section>
            <section class="auth-form-section">
                <div class="auth-form-container">
                    <img src="{{ asset('storage/logos/lg.png') }}" class="auth-logo" alt="Logo del sistema">
                    <p class="auth-kicker">Acceso al sistema</p>
                    <h2 class="auth-title">Recuperar contraseña</h2>
                    <p class="auth-description">¿Olvidó su contraseña? No hay problema. Simplemente indíquenos su dirección de correo electrónico y le enviaremos un enlace para restablecerla.</p>
                    <form method="POST" action="{{ route('password.email') }}" novalidate>
                        @csrf
                        <div class="auth-field auth-field-last">
                            <label for="email">Correo electrónico</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Ingrese su correo electrónico" autocomplete="username" required autofocus>
                        </div>
                        <button type="submit" class="auth-button">Enviar enlace de recuperación</button>
                    </form>
                    <nav class="auth-links" aria-label="Navegación de acceso">
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}">Volver al inicio de sesión</a>
                        @endif
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}">Crear una cuenta</a>
                        @endif
                    </nav>
                </div>
            </section>
        </div>
    </main>
    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                window.RSUValidation.success(@json(session('status')));
            });
        </script>
    @endif
</x-guest-layout>
