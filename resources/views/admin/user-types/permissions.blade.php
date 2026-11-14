@extends('adminlte::page')
@section('title', 'Permisos | RSU')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop
@section('content')
@php
    $allPermissions = $modules->flatMap(fn ($module) => $module->permissions->concat($module->children->flatMap(fn ($child) => $child->permissions)));
    $availableCount = $allPermissions->count();
    $assignedCount = count($assigned);
    $selectedSlugs = ['view', 'details', 'history', 'summary', 'map', 'helpers', 'options', 'bulk-context', 'bulk-modifications-view'];
    $managementSlugs = ['create', 'edit', 'delete', 'status', 'assign-type'];
@endphp
<div class="user-permission-matrix">
    <form id="permissionMatrixForm" method="post" action="{{ route('user-types.permissions.save', $type) }}">
        @csrf
        @method('PUT')
    <header class="upm-header">
        <div class="upm-heading">
            <span class="upm-heading-icon"><i class="bi bi-shield-lock"></i></span>
            <div><div class="upm-eyebrow">Administración de accesos</div><h1>{{ $type->name }}</h1><p>Selecciona las acciones disponibles para este tipo de usuario.</p></div>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('user-types.index') }}"><i class="bi bi-arrow-left me-1"></i>Volver</a>
    </header>

    <div class="upm-identity-fields card mb-4"><div class="row g-3">
        <div class="col-12 col-md-5"><label class="form-label" for="typeName">Nombre del tipo</label><input class="form-control" id="typeName" name="name" value="{{ old('name', $type->name) }}" required maxlength="100"></div>
        <div class="col-12 col-md-7"><label class="form-label" for="typeDescription">Descripción</label><textarea class="form-control" id="typeDescription" name="description" rows="2" maxlength="1000">{{ old('description', $type->description) }}</textarea></div>
    </div></div>

    <div class="upm-stats row g-3">
        <div class="col-6 col-xl-3"><div class="upm-stat"><span class="upm-stat-icon text-primary"><i class="bi bi-list-check"></i></span><div><small>Permisos disponibles</small><strong data-permission-available>{{ $availableCount }}</strong></div></div></div>
        <div class="col-6 col-xl-3"><div class="upm-stat"><span class="upm-stat-icon text-success"><i class="bi bi-check2-circle"></i></span><div><small>Permisos seleccionados</small><strong data-permission-selected>{{ $assignedCount }}</strong></div></div></div>
        <div class="col-6 col-xl-3"><div class="upm-stat"><span class="upm-stat-icon text-warning"><i class="bi bi-grid"></i></span><div><small>Módulos configurados</small><strong><span data-module-count>0</span><span class="upm-stat-total"> / {{ $modules->count() }}</span></strong></div></div></div>
        <div class="col-6 col-xl-3"><div class="upm-stat"><span class="upm-stat-icon text-info"><i class="bi bi-pie-chart"></i></span><div><small>Avance</small><strong data-permission-percent>0%</strong></div><div class="progress upm-progress"><div class="progress-bar" data-permission-progress role="progressbar" style="width:0%" aria-valuemin="0" aria-valuemax="100"></div></div></div></div>
    </div>

        @if($errors->any())<div class="alert alert-danger">Revisa las selecciones e inténtalo nuevamente.</div>@endif
        <div class="upm-global card mb-4"><div><strong>Seleccionar todos los permisos</strong><small>Activa o desactiva todas las acciones de la matriz.</small></div><label class="form-check form-switch mb-0"><input class="form-check-input permission-select-all" type="checkbox" data-scope="all" aria-label="Seleccionar todos los permisos"></label></div>

        @foreach($modules as $module)
            @php $modulePermissions = $module->permissions->concat($module->children->flatMap(fn ($child) => $child->permissions)); @endphp
            <section class="upm-module card" data-module-card>
                <div class="upm-module-header">
                    <div class="upm-module-title"><span class="upm-module-icon"><i class="{{ $module->icon ?: 'bi bi-grid' }}"></i></span><div><h2>{{ $module->name }}</h2><small><span data-module-selected>0</span> de {{ $modulePermissions->count() }} permisos seleccionados</small></div></div>
                    <label class="form-check form-switch mb-0"><input class="form-check-input permission-select-all" type="checkbox" data-scope="module-{{ $module->id }}" aria-label="Seleccionar todos los permisos de {{ $module->name }}"></label>
                </div>
                <div class="upm-submodules row g-3">
                    @foreach($module->children as $child)
                        <div class="col-12 col-lg-6 col-xxl-4">
                            <article class="upm-submodule card h-100" data-submodule-card>
                                <div class="upm-submodule-header"><div><h3>{{ $child->name }}</h3><small><span data-submodule-selected>0</span> de {{ $child->permissions->count() }} seleccionados</small></div><label class="form-check form-switch mb-0"><input class="form-check-input permission-select-all" type="checkbox" data-scope="submodule-{{ $child->id }}" aria-label="Seleccionar todos los permisos de {{ $child->name }}"></label></div>
                                @php
                                    $groups = ['Consulta' => [], 'Gestión' => [], 'Acciones especiales' => []];
                                    foreach ($child->permissions as $permission) {
                                        $action = substr($permission->slug, strrpos($permission->slug, '.') + 1);
                                        $group = in_array($action, $selectedSlugs, true) ? 'Consulta' : (in_array($action, $managementSlugs, true) ? 'Gestión' : 'Acciones especiales');
                                        $groups[$group][] = $permission;
                                    }
                                @endphp
                                <div class="upm-actions">
                                    @foreach($groups as $groupName => $permissions)
                                        @if(count($permissions))<div class="upm-action-group"><div class="upm-group-label">{{ $groupName }}</div><div class="upm-options">
                                            @foreach($permissions as $permission)
                                                <label class="form-check upm-option"><input class="form-check-input permission-option module-{{ $module->id }} submodule-{{ $child->id }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-category="{{ $groupName }}" @checked(in_array($permission->id, $assigned, true))><span class="form-check-label">{{ $permission->name }}</span></label>
                                            @endforeach
                                        </div></div>@endif
                                    @endforeach
                                </div>
                            </article>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <div class="upm-sticky-actions"><div class="upm-sticky-inner"><span><strong data-sticky-count>{{ $assignedCount }}</strong> permisos seleccionados</span><div><a class="btn btn-outline-secondary" href="{{ route('user-types.index') }}">Cancelar</a><button class="btn btn-success" type="submit"><i class="bi bi-check2 me-1"></i>Guardar permisos</button></div></div></div>
    </form>
</div>
@stop
@section('js')
<script>
(() => {
    const form = document.getElementById('permissionMatrixForm');
    if (!form) return;
    const options = [...form.querySelectorAll('.permission-option')];
    const selectors = [...form.querySelectorAll('.permission-select-all')];
    const count = items => items.filter(item => item.checked).length;
    const refresh = () => {
        selectors.forEach(control => {
            const scoped = control.dataset.scope === 'all' ? options : [...form.querySelectorAll('.' + control.dataset.scope)];
            const selected = count(scoped);
            control.checked = scoped.length > 0 && selected === scoped.length;
            control.indeterminate = selected > 0 && selected < scoped.length;
        });
        form.querySelectorAll('[data-module-card]').forEach(card => {
            const moduleOptions = [...card.querySelectorAll('.permission-option')];
            card.querySelector('[data-module-selected]').textContent = count(moduleOptions);
            card.dataset.configured = count(moduleOptions) > 0 ? '1' : '0';
        });
        form.querySelectorAll('[data-submodule-card]').forEach(card => {
            card.querySelector('[data-submodule-selected]').textContent = count([...card.querySelectorAll('.permission-option')]);
        });
        const selected = count(options);
        const percent = options.length ? Math.round(selected * 100 / options.length) : 0;
        document.querySelector('[data-permission-selected]').textContent = selected;
        document.querySelector('[data-sticky-count]').textContent = selected;
        document.querySelector('[data-module-count]').textContent = form.querySelectorAll('[data-module-card][data-configured="1"]').length;
        document.querySelector('[data-permission-percent]').textContent = percent + '%';
        const progress = document.querySelector('[data-permission-progress]');
        progress.style.width = percent + '%';
        progress.setAttribute('aria-valuenow', percent);
    };
    selectors.forEach(control => control.addEventListener('change', () => {
        const scoped = control.dataset.scope === 'all' ? options : [...form.querySelectorAll('.' + control.dataset.scope)];
        scoped.forEach(option => option.checked = control.checked);
        refresh();
    }));
    options.forEach(option => option.addEventListener('change', refresh));
    refresh();
})();
</script>
@stop
