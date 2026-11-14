<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-6 py-12">
        <div class="w-full max-w-md rounded-lg bg-white p-8 text-center shadow dark:bg-gray-800">
            <div class="mb-4 text-4xl text-gray-500"><i class="bi bi-shield-lock"></i></div>
            <h1 class="mb-2 text-xl font-semibold text-gray-900 dark:text-white">Acceso restringido</h1>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">Tu cuenta todavía no tiene módulos autorizados. Solicita al administrador que revise tus permisos.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">Cerrar sesión</button>
            </form>
        </div>
    </div>
</x-guest-layout>
