<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 px-5 py-12 dark:bg-gray-900">
        <main class="w-full max-w-xl">
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
                <div class="px-7 pb-8 pt-10 text-center sm:px-10">
                    <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300">
                        <i class="bi bi-shield-lock-fill text-4xl" aria-hidden="true"></i>
                    </div>

                    <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                        Acceso no habilitado
                    </span>

                    <h1 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">
                        No tienes acceso al sistema
                    </h1>

                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Tu cuenta inició sesión correctamente, pero todavía no tiene módulos habilitados para utilizar el sistema.
                    </p>

                    <div class="mt-7 flex gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4 text-left dark:border-blue-900/60 dark:bg-blue-900/20">
                        <i class="bi bi-info-circle-fill mt-0.5 shrink-0 text-lg text-blue-600 dark:text-blue-300" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">¿Qué debes hacer?</p>
                            <p class="mt-1 text-sm leading-6 text-blue-800 dark:text-blue-200">
                                Comunícate con el administrador para que revise tu tipo de usuario y te asigne los módulos y permisos correspondientes.
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-8">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 dark:focus:ring-offset-gray-800 sm:w-auto">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            Cerrar sesión
                        </button>
                    </form>
                </div>

                <footer class="border-t border-gray-100 bg-gray-50 px-7 py-4 text-center dark:border-gray-700 dark:bg-gray-800/70">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        El acceso a las opciones depende de los permisos asignados a tu cuenta.
                    </p>
                </footer>
            </section>
        </main>
    </div>
</x-guest-layout>
