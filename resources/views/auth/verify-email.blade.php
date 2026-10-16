<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-4 text-sm text-gray-600">
            Antes de continuar, verifica tu correo electrónico con el enlace que te enviamos. Si no lo recibiste, podemos enviarte otro.
        </div>

        @if (session('status') == 'verification-link-sent')
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    window.RSUValidation.success('Enviamos un nuevo enlace de verificación al correo electrónico de tu perfil.');
                });
            </script>
        @endif

        <div class="mt-4 flex items-center justify-between">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <div>
                    <x-button type="submit">
                        Reenviar correo de verificación
                    </x-button>
                </div>
            </form>

            <div>
                <a
                    href="{{ route('profile.show') }}"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                    Editar perfil</a>

                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf

                    <button type="submit" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 ms-2">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>
