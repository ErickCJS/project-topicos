<x-guest-layout>
    <main class="flex min-h-screen w-full flex-col md:flex-row">
        <section class="relative hidden min-h-screen flex-col justify-end bg-cover bg-center p-8 text-white md:flex md:w-1/2 md:p-12"
            style="background-image: url('https://scontent.fcix4-1.fna.fbcdn.net/v/t39.30808-6/493520269_1295107909129480_3447796301901916944_n.jpg?stp=dst-jpg_tt6&cstp=mx2048x1365&ctp=s2048x1365&_nc_cat=105&_nc_map=urlgen_bucketless&ccb=1-7&_nc_sid=127cfc&_nc_ohc=7X0rK128m7IQ7kNvwGYhlQD&_nc_oc=Ado--cBnyiRwPrsISdP-xtKItPXpn2uxzAvSUpTpR2xq9bxDx6_CKCrc46M6BkLofsU&_nc_zt=23&_nc_ht=scontent.fcix4-1.fna&_nc_gid=Wv4l0h-xKlcedMUcHKiXPA&_nc_ss=7b2a8&oh=00_AQMOraO8AtEgETVLUtsx1fTxlOxSvh0iGxGp6I1Db96mJg&oe=6AD249C1');">
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
                <h2 class="mb-2 mt-2 text-center text-3xl font-bold tracking-tight text-blue-700 sm:text-4xl">Recuperar Contraseña</h2>
                <p class="mb-7 text-center text-sm leading-relaxed text-gray-600">¿Olvidó su contraseña? No hay problema. Simplemente indíquenos su dirección de correo electrónico y le enviaremos un enlace para restablecerla.</p>

                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" novalidate>
                    @csrf
                    <div class="mb-5">
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-800">Correo</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="Ingrese su correo electrónico" autocomplete="username" required autofocus class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Enviar enlace de recuperación</button>
                </form>
            </div>
        </section>
    </main>
    @include('components.alerts-auth')
</x-guest-layout>
