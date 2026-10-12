<x-guest-layout>
    <main class="min-h-screen flex items-center justify-center p-4 bg-gray-100">
        <div class="w-full max-w-md">
            <img src="https://jloenlinea.munijlo.gob.pe/assets/modern-logo-EliRhkgI.png" alt="Logo Municipalidad" class="w-64 mx-auto mb-6">

            <section class="w-full rounded-2xl bg-white px-6 py-8 shadow-xl sm:px-8">
                <h1 class="mb-6 text-center text-2xl font-semibold text-gray-900">Iniciar sesión</h1>

                <x-validation-errors class="mb-4" />

                @session('status')
                    <div class="mb-4 text-sm font-medium text-green-700">{{ $value }}</div>
                @endsession

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-1 block text-sm font-medium text-gray-800">Correo electrónico</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-sm font-medium text-gray-800">Contraseña</label>
                        <input id="password" name="password" type="password" required autocomplete="current-password"
                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <label for="remember_me" class="flex items-center gap-2 text-sm text-gray-700">
                        <x-checkbox id="remember_me" name="remember" class="rounded border-gray-300 text-slate-800 focus:ring-blue-500" />
                        <span>Mantener sesión activa</span>
                    </label>

                    <div class="flex flex-col-reverse items-center justify-between gap-4 pt-2 sm:flex-row">
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-blue-700 underline hover:text-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                ¿Olvidó su contraseña?
                            </a>
                        @else
                            <span></span>
                        @endif

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-slate-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto">
                            INICIAR SESIÓN
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </main>
</x-guest-layout>
