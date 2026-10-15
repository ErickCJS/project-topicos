<nav class="app-header navbar rsu-topbar">
    <div class="container-fluid">
        <ul class="navbar-nav">
            @include('adminlte::partials.navbar.menu-item-left-sidebar-toggler')
        </ul>

        <div class="ms-auto rsu-topbar-tools ">
            <button type="button" class="rsu-topbar-theme" id="rsu-theme-toggle" aria-label="Cambiar a tema oscuro" title="Cambiar tema">
                <svg class="rsu-theme-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                <svg class="rsu-theme-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5 8.5 8.5 0 1 0 20.5 14.2Z"/></svg>
            </button>

            @auth
                <div class="dropdown">
                    <button type="button" class="rsu-topbar-profile dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="rsu-topbar-user">
                            <strong>{{ auth()->user()->name }}</strong>
                            <small>{{ auth()->user()->email }}</small>
                        </span>
                        <svg class="rsu-profile-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end rsu-profile-menu">
                        <div class="rsu-profile-menu-heading">
                            <strong>{{ auth()->user()->name }}</strong>
                            <span>{{ auth()->user()->email }}</span>
                        </div>
                        <a href="{{ route('profile.show') }}" class="dropdown-item rsu-profile-menu-item">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>
                            <span>Mi perfil</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item rsu-profile-menu-item rsu-profile-logout">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5m5 5H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                                <span>Cerrar sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</nav>

@push('css')
    <style>
        .rsu-topbar {
            min-height: 58px;
            padding: 0 18px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
        }

        .rsu-topbar .container-fluid,
        .rsu-topbar-profile,
        .rsu-topbar-theme {
            display: flex;
            align-items: center;
        }

        .rsu-topbar .container-fluid {
            gap: 18px;
        }

        .rsu-topbar-tools {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 5px;
            border-radius: 11px;
            transition: background-color 300ms ease;
        }

        .rsu-topbar .nav-link {
            display: grid;
            width: 36px;
            height: 36px;
            place-items: center;
            border-radius: 7px;
            color: var(--sidebar-muted);
        }

        .rsu-topbar .nav-link:hover {
            background: var(--sidebar-hover);
            color: var(--sidebar-text);
        }

        .rsu-topbar-theme {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            place-items: center;
            padding: 0;
            border: 0;
            border-radius: 50%;
            color: var(--sidebar-text);
            cursor: pointer;
            transition: background-color 150ms ease;
            margin-right: 0.5rem;
        }

        .rsu-topbar-theme:hover {
            background: var(--sidebar-hover);
            color: var(--sidebar-text);
        }

        .rsu-topbar-theme svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.5;
        }

        .rsu-theme-moon,
        html.dark .rsu-theme-sun {
            display: none;
        }

        html.dark .rsu-theme-moon {
            display: block;
        }

        .rsu-topbar-profile {
            display: flex;
            min-height: 40px;
            gap: 9px;
            padding: 3px 12px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: var(--sidebar-text);
            font: inherit;
            text-decoration: none;
            text-align: left;
            cursor: pointer;
            transition: background-color 150ms ease;
        }

        .rsu-topbar-profile:hover {
            background: var(--sidebar-hover);
            color: var(--sidebar-text);
        }

        .rsu-topbar-profile::after {
            display: none;
        }

        .rsu-profile-chevron {
            width: 15px;
            height: 15px;
            margin-left: 2px;
            fill: none;
            stroke: var(--sidebar-muted);
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.5;
        }

        .rsu-profile-menu {
            min-width: 230px;
            margin-top: 8px !important;
            padding: 7px;
            border: 0;
            border-radius: 10px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            box-shadow: 0 12px 32px rgb(0 0 0 / 18%);
        }

        .rsu-profile-menu-heading {
            display: grid;
            gap: 3px;
            padding: 9px 10px 11px;
        }

        .rsu-profile-menu-heading strong {
            overflow: hidden;
            color: var(--sidebar-text);
            font-size: 12px;
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rsu-profile-menu-heading span {
            overflow: hidden;
            color: var(--sidebar-muted);
            font-size: 11px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rsu-profile-menu-item {
            display: flex;
            min-height: 36px;
            align-items: center;
            gap: 9px;
            padding: 8px 10px;
            border-radius: 6px;
            background: transparent;
            color: var(--sidebar-text);
            font-size: 12px;
            text-decoration: none;
        }

        .rsu-profile-menu-item:hover,
        .rsu-profile-menu-item:focus {
            background: var(--sidebar-hover);
            color: var(--sidebar-text);
        }

        .rsu-profile-menu-item svg {
            width: 16px;
            height: 16px;
            flex: 0 0 16px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 1.5;
        }

        .rsu-profile-menu form {
            margin: 0;
        }

        .rsu-profile-logout {
            width: 100%;
            border: 0;
            text-align: left;
            cursor: pointer;
        }

        .rsu-topbar-avatar {
            display: grid;
            width: 30px;
            height: 30px;
            flex: 0 0 30px;
            place-items: center;
            border-radius: 50%;
            color: var(--sidebar-muted);
            font-size: 11px;
            font-weight: 600;
        }

        .rsu-topbar-user strong,
        .rsu-topbar-user small {
            display: block;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rsu-topbar-user strong {
            font-size: 12px;
            font-weight: 600;
        }

        .rsu-topbar-user small {
            margin-top: 2px;
            color: var(--sidebar-muted);
            font-size: 11px;
        }

        @media (max-width: 575px) {
            .rsu-topbar {
                padding: 0 8px;
            }

            .rsu-topbar-tools {
                gap: 2px;
                padding: 3px;
            }

            .rsu-topbar-user small,
            .rsu-profile-chevron {
                display: none;
            }

            .rsu-topbar-user strong {
                max-width: 70px;
            }

            .rsu-topbar-profile {
                gap: 5px;
                padding: 3px 7px;
            }

            .rsu-topbar .container-fluid {
                gap: 4px;
            }
        }
    </style>
@endpush

@push('js')
    <script>
        (() => {
            const root = document.documentElement;
            const toggle = document.getElementById('rsu-theme-toggle');

            if (!toggle) return;

            const updateLabel = () => {
                const isDark = root.classList.contains('dark');
                toggle.setAttribute('aria-label', isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
                toggle.setAttribute('title', isDark ? 'Cambiar a tema claro' : 'Cambiar a tema oscuro');
            };

            updateLabel();
            toggle.addEventListener('click', () => {
                const isDark = root.classList.toggle('dark');
                root.setAttribute('data-bs-theme', isDark ? 'dark' : 'light');
                updateLabel();

                try {
                    localStorage.setItem('rsu-theme', isDark ? 'dark' : 'light');
                } catch (error) {
                    // The selected theme still applies for the current page.
                }
            });
        })();
    </script>
@endpush
