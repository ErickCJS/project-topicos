@php
    $editing = isset($type);
    $allPermissions = $modules->flatMap(
        fn ($module) => $module->permissions->concat(
            $module->children->flatMap(fn ($child) => $child->permissions)
        )
    )->unique('id')->values();
    $assigned = array_map('intval', $assigned ?? []);
@endphp

<div class="modal-header users-modal-header">
    <div class="users-modal-heading">
        <span class="users-modal-icon">
            <i class="bi {{ $editing ? 'bi-shield-check' : 'bi-shield-plus' }}" aria-hidden="true"></i>
        </span>
        <div>
            <h5 class="modal-title mb-0">
                {{ $editing ? 'Editar tipo y permisos' : 'Nuevo tipo de usuario' }}
            </h5>
            <small class="text-muted">
                Define qué podrá hacer un usuario al recibir este tipo.
            </small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<form
    id="userTypeForm"
    action="{{ $editing ? route('user-types.update', $type->id) : route('user-types.store') }}"
    method="POST"
    class="users-modal-form">

    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="modal-body users-form-body">
        <div class="row g-3">
            <div class="col-12 col-md-5">
                <label for="user_type_name" class="form-label">
                    Nombre del tipo de usuario <span class="text-danger">*</span>
                </label>
                <input
                    type="text"
                    class="form-control"
                    id="user_type_name"
                    name="name"
                    value="{{ old('name', $type->name ?? '') }}"
                    maxlength="100"
                    placeholder="Ej. Administrativo, Supervisor u Operador"
                    autocomplete="off"
                    required
                >
            </div>

            <div class="col-12 col-md-7">
                <label for="user_type_description" class="form-label">Descripción</label>
                <input
                    type="text"
                    class="form-control"
                    id="user_type_description"
                    name="description"
                    value="{{ old('description', $type->description ?? '') }}"
                    maxlength="255"
                    placeholder="Describe brevemente las responsabilidades del tipo"
                >
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h6 class="mb-1">Permisos predeterminados</h6>
                <p class="small text-muted mb-0">
                    Selecciona los módulos y acciones que tendrá esta plantilla.
                </p>
            </div>
            <div class="text-end">
                <div class="fw-semibold">
                    <span data-type-selected>{{ count($assigned) }}</span>
                    <span class="text-muted">/ {{ $allPermissions->count() }}</span>
                </div>
                <small class="text-muted">permisos seleccionados</small>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-sm btn-outline-success" data-type-preset="all">
                <i class="bi bi-check-all me-1"></i>Todos los permisos
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-type-preset="none">
                <i class="bi bi-x-lg me-1"></i>Ninguno
            </button>
            <div class="form-check form-switch ms-auto mb-0">
                <input
                    class="form-check-input"
                    type="checkbox"
                    id="user_type_select_all"
                    data-type-select-all
                >
                <label class="form-check-label small" for="user_type_select_all">
                    Seleccionar todo
                </label>
            </div>
        </div>

        @forelse($modules as $module)
            @php
                $modulePermissions = $module->permissions->concat(
                    $module->children->flatMap(fn ($child) => $child->permissions)
                )->unique('id')->values();
                $moduleSelected = $modulePermissions->filter(
                    fn ($permission) => in_array((int) $permission->id, $assigned, true)
                )->count();
            @endphp

            <section class="card mb-3 border">
                <div class="card-header bg-body-tertiary d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="{{ $module->icon ?: 'bi bi-grid' }} text-primary"></i>
                        <strong>{{ $module->name }}</strong>
                        <span class="badge bg-secondary-subtle text-secondary-emphasis">
                            {{ $moduleSelected }}/{{ $modulePermissions->count() }}
                        </span>
                    </div>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-secondary"
                        data-type-module="{{ $module->id }}"
                        data-type-module-toggle
                    >
                        Seleccionar módulo
                    </button>
                </div>

                <div class="card-body">
                    @if($module->permissions->isNotEmpty())
                        <div class="mb-3">
                            <div class="small fw-semibold text-muted mb-2">General</div>
                            <div class="row g-2">
                                @foreach($module->permissions as $permission)
                                    <div class="col-12 col-sm-6 col-lg-4">
                                        <label class="form-check border rounded p-2 ps-5 h-100">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                data-type-permission
                                                data-type-module-id="{{ $module->id }}"
                                                @checked(in_array((int) $permission->id, $assigned, true))
                                            >
                                            <span class="form-check-label">{{ $permission->name }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach($module->children as $child)
                        @if($child->permissions->isNotEmpty())
                            <div class="border rounded p-3 mb-2">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <strong class="small">{{ $child->name }}</strong>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-link text-decoration-none p-0"
                                        data-type-submodule="{{ $child->id }}"
                                        data-type-submodule-toggle
                                    >
                                        Alternar todos
                                    </button>
                                </div>
                                <div class="row g-2">
                                    @foreach($child->permissions as $permission)
                                        <div class="col-12 col-sm-6 col-lg-4">
                                            <label class="form-check border rounded p-2 ps-5 h-100">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->id }}"
                                                    data-type-permission
                                                    data-type-module-id="{{ $module->id }}"
                                                    data-type-submodule-id="{{ $child->id }}"
                                                    @checked(in_array((int) $permission->id, $assigned, true))
                                                >
                                                <span class="form-check-label">{{ $permission->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </section>
        @empty
            <div class="alert alert-warning mb-0">
                El catálogo de permisos todavía no está generado. Ejecuta
                <code>php artisan permissions:sync-routes</code> y vuelve a abrir este formulario.
            </div>
        @endforelse

        <div class="alert alert-info d-flex align-items-start gap-2 mt-3 mb-0">
            <i class="bi bi-info-circle mt-1" aria-hidden="true"></i>
            <div class="small">
                Esta plantilla se copia al crear un usuario o al cambiarlo a este tipo.
                Los permisos personalizados de usuarios ya asignados no se sobrescriben al editar la plantilla.
                El acceso administrativo total sigue siendo una configuración independiente.
            </div>
        </div>
    </div>

    <div class="modal-footer users-modal-footer">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
            Cancelar
        </button>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-floppy me-1"></i>
            {{ $editing ? 'Guardar tipo y permisos' : 'Crear tipo' }}
        </button>
    </div>
</form>
