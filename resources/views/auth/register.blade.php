<x-guest-layout>
    <main class="flex min-h-screen w-full flex-col md:flex-row">
        <section class="relative hidden min-h-screen flex-col justify-end bg-cover bg-center p-8 text-white md:flex md:w-1/2 md:p-12"
            style="background-image: url('/storage/logos/91a3d343-e34e-41bc-97fb-f8c5d6790e23.jpg');">
            <div class="absolute inset-0 bg-black/40"></div>
            <div class="relative z-10 mt-auto max-w-2xl">
                <h1 class="mb-5 text-4xl font-bold leading-tight lg:text-5xl">Gestionando residuos para un JLO más limpio.</h1>
                <p class="max-w-xl text-base leading-relaxed text-white/90 lg:text-lg">Plataforma para la gestión, planificación y supervisión de las operaciones de recolección de residuos en José Leonardo Ortiz.</p>
                <footer class="mt-10 border-t border-white/30 pt-5 text-xs font-medium tracking-wide text-white/80 sm:text-sm">© 2026 USAT - UNIVERSIDAD CATÓLICA SANTO TORIBIO DE MOGROVEJO</footer>
            </div>
        </section>

        <section class="flex min-h-screen w-full items-center justify-center bg-white px-5 py-10 sm:px-8 md:w-1/2 md:px-10 lg:px-16">
            <div class="w-full max-w-md">
                <img src="https://jloenlinea.munijlo.gob.pe/assets/modern-logo-EliRhkgI.png" alt="Municipalidad José Leonardo Ortiz" class="mx-auto mb-4 w-48 max-w-full">
                <p class="text-center text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">ACCESO AL SISTEMA</p>
                <h2 class="mb-2 mt-2 text-center text-3xl font-bold tracking-tight text-blue-700 sm:text-4xl">Crear una cuenta</h2>
                <p class="mb-7 text-center text-sm leading-relaxed text-gray-600">Complete el formulario para registrarse en el sistema.</p>

                <form method="POST" action="{{ route('register') }}" novalidate>
                    @csrf
                    <div class="mb-4">
                        <label for="name" class="mb-1.5 block text-sm font-medium text-gray-800">Nombre</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Ingrese su nombre" autocomplete="name" required autofocus class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-800">Correo</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Ingrese su correo electrónico" autocomplete="username" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-800">Contraseña</label>
                        <input id="password" type="password" name="password" placeholder="Ingrese su contraseña" autocomplete="new-password" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-800">Confirmar Contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repita su contraseña" autocomplete="new-password" required class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>

                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                        <div class="mb-4">
                            <label for="terms" class="flex items-start gap-2 text-sm text-gray-700">
                                <x-checkbox name="terms" id="terms" required class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                <span>Acepto los <a target="_blank" href="{{ route('terms.show') }}" class="text-blue-700 underline hover:text-blue-900">Términos del servicio</a> y la <a target="_blank" href="{{ route('policy.show') }}" class="text-blue-700 underline hover:text-blue-900">Política de privacidad</a>.</span>
                            </label>
                        </div>
                    @endif

                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Registrarse</button>
                </form>

                <p class="mt-6 border-t border-gray-200 pt-5 text-center text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <a href="{{ route('login') }}" class="ml-1 font-semibold text-blue-700 transition hover:text-blue-900 hover:underline">Inicia sesión</a>
                </p>
            </div>
        </section>
    </main>
</x-guest-layout>
