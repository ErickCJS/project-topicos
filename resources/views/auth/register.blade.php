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
                    <h2 class="auth-title">Crear una cuenta</h2>
                    <p class="auth-description">Complete el formulario para registrarse en el sistema.</p>
                    <form method="POST" action="{{ route('register') }}" novalidate>
                        @csrf
                        <div class="auth-field">
                            <label for="name">Nombre</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Ingrese su nombre" autocomplete="name" required autofocus>
                        </div>
                        <div class="auth-field">
                            <label for="email">Correo electrónico</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Ingrese su correo electrónico" autocomplete="username" required>
                        </div>
                        <div class="auth-field">
                            <label for="password">Contraseña</label>
                            <input id="password" type="password" name="password" placeholder="Ingrese su contraseña" autocomplete="new-password" required>
                        </div>
                        <div class="auth-field">
                            <label for="password_confirmation">Confirmar contraseña</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repita su contraseña" autocomplete="new-password" required>
                        </div>
                        @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                            <div class="auth-terms">
                                <label for="terms">
                                    <x-checkbox name="terms" id="terms" required />
                                    <span>Acepto los <a target="_blank" href="{{ route('terms.show') }}">Términos del servicio</a> y la <a target="_blank" href="{{ route('policy.show') }}">Política de privacidad</a>.</span>
                                </label>
                            </div>
                        @endif
                        <button type="submit" class="auth-button">Registrarse</button>
                    </form>
                    <p class="auth-navigation">¿Ya tienes una cuenta? <a href="{{ route('login') }}">Inicia sesión</a></p>
                </div>
            </section>
        </div>
    </main>
</x-guest-layout>
