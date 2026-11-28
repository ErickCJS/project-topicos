@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

<aside class="{{ $layoutHelper->makeSidebarWrapperClasses() }}" {!! $layoutHelper->makeSidebarData() !!}>
    <a href="{{ route('admin.home') }}" class="rsu-sidebar-brand" title="Ir al inicio del Sistema de Gestión RSU">
        <img
            src="https://jloenlinea.munijlo.gob.pe/denuncia-consumidor/assets/logo-DBHCGE0v.jpeg"
            alt="Escudo de la Municipalidad Distrital de José Leonardo Ortiz"
            class="rsu-sidebar-logo"
            loading="eager"
            referrerpolicy="no-referrer">
        <span class="rsu-sidebar-brand-copy">
            <strong>SGRS JLO</strong>
            <small>Sistema de Gestión RSU</small>
        </span>
    </a>

    <div class="rsu-sidebar-nav">
        <nav id="navigation" aria-label="Navegación principal" class="mt-2">
            <ul class="{{ $layoutHelper->makeSidebarNavClasses() }}"
                data-lte-toggle="treeview"
                {!! $layoutHelper->makeSidebarNavData() !!}>
                @each('adminlte::partials.sidebar.menu-item', $adminlte->menu('sidebar'), 'item')
            </ul>
        </nav>
    </div>
</aside>
