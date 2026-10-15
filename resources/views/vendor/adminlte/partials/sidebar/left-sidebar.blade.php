@inject('layoutHelper', 'JeroenNoten\LaravelAdminLte\Helpers\LayoutHelper')

<aside class="{{ $layoutHelper->makeSidebarWrapperClasses() }}" {!! $layoutHelper->makeSidebarData() !!}>
    <a href="{{ route('admin.home') }}" class="rsu-sidebar-brand">
        <span aria-hidden="true">SGRS JLO</span>
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
