<x-guest-layout>
    <style>
        .restricted-screen {
            background:
                radial-gradient(circle at 12% 15%, rgba(14, 116, 144, .09), transparent 25rem),
                radial-gradient(circle at 88% 82%, rgba(37, 99, 235, .07), transparent 28rem),
                #f3f6fb;
        }
        .restricted-panel {
            background: linear-gradient(145deg, #102b46 0%, #123b55 55%, #145568 100%);
        }
        .restricted-grid {
            background-image: linear-gradient(rgba(255,255,255,.045) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.045) 1px, transparent 1px);
            background-size: 30px 30px;
            mask-image: linear-gradient(to bottom, black, transparent 88%);
        }
        .restricted-orb {
            background: radial-gradient(circle, rgba(45, 212, 191, .24) 0%, rgba(45, 212, 191, .07) 42%, transparent 72%);
        }
        .restricted-button {
            background: linear-gradient(100deg, #087f75 0%, #0f766e 100%);
            box-shadow: 0 10px 22px rgba(15, 118, 110, .18);
        }
        .restricted-button:hover {
            background: linear-gradient(100deg, #066b64 0%, #115e59 100%);
            transform: translateY(-1px);
            box-shadow: 0 13px 25px rgba(15, 118, 110, .24);
        }
        @media (prefers-reduced-motion: reduce) {
            .restricted-button { transition: none !important; }
        }
    </style>

    <div class="restricted-screen relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-8 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute -left-24 top-16 h-72 w-72 rounded-full bg-cyan-100/60 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 right-0 h-96 w-96 rounded-full bg-blue-100/70 blur-3xl"></div>

        <main class="relative z-10 w-full max-w-5xl">
            <div class="mb-5 flex items-center justify-between px-1">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-sm font-extrabold tracking-tight text-white shadow-sm">
                        RSU
                    </div>
                    <div>
                        <p class="text-sm font-bold tracking-wide text-slate-800">SISTEMA DE GESTIÓN</p>
                        <p class="text-[11px] font-medium uppercase tracking-[.16em] text-slate-500">Portal institucional</p>
                    </div>
                </div>
                <div class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm sm:flex">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    Acceso pendiente
                </div>
            </div>

            <section class="grid overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-[0_28px_80px_-35px_rgba(15,23,42,.32)] md:grid-cols-[.88fr_1.12fr]">
                <div class="restricted-panel relative isolate flex min-h-[300px] flex-col justify-between overflow-hidden p-8 text-white sm:p-10 md:min-h-[570px]">
                    <div class="restricted-grid absolute inset-0 -z-10 opacity-80"></div>
                    <div class="restricted-orb absolute -right-24 top-10 -z-10 h-80 w-80 rounded-full"></div>
                    <div class="restricted-orb absolute -bottom-36 -left-24 -z-10 h-80 w-80 rounded-full"></div>

                    <div class="relative">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[.18em] text-cyan-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                            Control de acceso
                        </div>
                    </div>

                    <div class="relative flex flex-1 flex-col items-center justify-center py-9 text-center md:py-0">
                        <div class="relative mb-7 flex h-36 w-36 items-center justify-center rounded-full border border-white/15 bg-white/[.07] shadow-[0_0_70px_rgba(45,212,191,.12)]">
                            <div class="absolute inset-3 rounded-full border border-dashed border-cyan-100/25"></div>
                            <div class="flex h-24 w-24 items-center justify-center rounded-[28px] bg-gradient-to-br from-cyan-300 to-teal-500 text-slate-950 shadow-xl shadow-cyan-950/25">
                                <svg viewBox="0 0 64 64" class="h-14 w-14" fill="none" aria-hidden="true">
                                    <path d="M32 6 52 14v15c0 13.2-8.5 23.5-20 29C20.5 52.5 12 42.2 12 29V14L32 6Z" stroke="currentColor" stroke-width="3.5" stroke-linejoin="round"/>
                                    <rect x="23" y="28" width="18" height="15" rx="3" stroke="currentColor" stroke-width="3.5"/>
                                    <path d="M27 28v-5a5 5 0 0 1 10 0v5" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
                                    <path d="M32 34v3" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <span class="absolute -bottom-1 -right-1 flex h-9 w-9 items-center justify-center rounded-full border-4 border-[#123b55] bg-amber-400 text-slate-900">
                                <svg viewBox="0 0 20 20" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.72-1.36 3.486 0l6.1 10.844c.75 1.333-.213 2.99-1.743 2.99H3.9c-1.53 0-2.493-1.657-1.743-2.99l6.1-10.844ZM11 13a1 1 0 1 1-2 0 1 1 0 0 1 2 0Zm-1-6a1 1 0 0 0-1 1v2a1 1 0 1 0 2 0V8a1 1 0 0 0-1-1Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </div>

                        <p class="mb-2 text-xs font-bold uppercase tracking-[.2em] text-cyan-200">Acceso protegido</p>
                        <h2 class="max-w-xs text-2xl font-bold leading-tight tracking-tight sm:text-3xl">
                            Cada usuario accede solo a lo que necesita.
                        </h2>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-blue-100/75">
                            Los módulos disponibles dependen de los permisos asignados a tu cuenta.
                        </p>
                    </div>

                    <div class="relative flex items-center justify-between border-t border-white/10 pt-5 text-[11px] text-blue-100/60">
                        <span>Seguridad y control de accesos</span>
                        <span class="font-semibold tracking-wider text-cyan-100/80">RSU</span>
                    </div>
                </div>

                <div class="flex flex-col justify-center p-7 sm:p-10 lg:p-14">
                    <div class="mb-7 inline-flex w-fit items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[.16em] text-amber-700">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Sin módulos asignados
                    </div>

                    <h1 class="max-w-lg text-3xl font-extrabold leading-[1.12] tracking-tight text-slate-900 sm:text-4xl">
                        Tu cuenta aún no tiene acceso al sistema
                    </h1>

                    <p class="mt-5 max-w-lg text-sm leading-7 text-slate-600 sm:text-base">
                        Has iniciado sesión correctamente, pero todavía no tienes módulos habilitados para trabajar en la plataforma.
                    </p>

                    <div class="mt-8 rounded-2xl border border-sky-100 bg-sky-50/80 p-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm">
                                <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <path d="M12 11v5m0-8h.01" stroke-linecap="round"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">¿Cómo puedes obtener acceso?</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600">
                                    Comunícate con el administrador para que revise tu tipo de usuario y te asigne los módulos y permisos correspondientes.
                                </p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}" class="mt-9">
                        @csrf
                        <button type="submit" class="restricted-button inline-flex w-full items-center justify-center gap-3 rounded-xl px-6 py-4 text-sm font-bold text-white transition duration-200 focus:outline-none focus:ring-4 focus:ring-teal-200 sm:w-auto">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M10 17l5-5-5-5M15 12H3" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M12 3h5a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-5" stroke-linecap="round"></path>
                            </svg>
                            Cerrar sesión
                        </button>
                    </form>

                    <div class="mt-8 border-t border-slate-100 pt-5">
                        <p class="text-xs leading-5 text-slate-400">
                            Por seguridad, el sistema no permite ingresar a módulos que no hayan sido autorizados para tu cuenta.
                        </p>
                    </div>
                </div>
            </section>

            <p class="mt-5 text-center text-[11px] font-medium tracking-wide text-slate-400">
                SISTEMA DE GESTIÓN · ACCESO SEGURO
            </p>
        </main>
    </div>
</x-guest-layout>
