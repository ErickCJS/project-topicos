@extends('adminlte::page')
@section('title', 'Tipos de usuario | RSU')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin-indexes.css') }}">
@stop
@section('content')
<div class="users-module user-types-page">
    <div class="user-types-heading"><div><span class="user-types-eyebrow">Administración de accesos</span><h1><i class="bi bi-person-badge me-2"></i>Tipos de usuario</h1><p>Organiza las cuentas y administra sus permisos.</p></div></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->has('user_type'))<div class="alert alert-danger">{{ $errors->first('user_type') }}</div>@endif
    @if($errors->has('name'))<div class="alert alert-danger">{{ $errors->first('name') }}</div>@endif

    <section class="card user-type-create-card mb-4">
        <div class="card-header"><strong><i class="bi bi-plus-circle me-2"></i>Crear tipo de usuario</strong></div>
        <div class="card-body"><form method="post" action="{{ route('user-types.store') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-12 col-lg-4"><label class="form-label" for="newTypeName">Nombre</label><input class="form-control" id="newTypeName" name="name" required maxlength="100" value="{{ old('name') }}"></div>
            <div class="col-12 col-lg-6"><label class="form-label" for="newTypeDescription">Descripción</label><input class="form-control" id="newTypeDescription" name="description" maxlength="1000" value="{{ old('description') }}"></div>
            <div class="col-12 col-lg-2"><button class="btn btn-success w-100" type="submit"><i class="bi bi-plus-lg me-1"></i>Crear tipo</button></div>
        </form></div>
    </section>

    <section class="card user-types-table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 user-types-table">
                <thead><tr><th>Nombre del tipo</th><th>Descripción</th><th class="text-center">Usuarios asignados</th><th>Estado</th><th>Última actualización</th><th class="text-center">Acciones</th></tr></thead>
                <tbody>
                @forelse($types as $type)
                    @php
                        $isAdministrator = $type->is_admin || $type->slug === 'administrador';
                        $isIdentityProtected = $isAdministrator || $type->slug === 'pendiente-de-asignacion';
                    @endphp
                    <tr>
                        <td><div class="user-type-name">{{ $type->name }}</div></td>
                        <td class="user-type-description">{{ $type->description ?: 'Sin descripción' }}</td>
                        <td class="text-center"><span class="user-type-count">{{ $type->users_count }}</span></td>
                        <td><span class="user-type-status {{ $type->is_active ? 'is-active' : 'is-inactive' }}"><span></span>{{ $type->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="user-type-date">{{ $type->updated_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        <td><div class="user-type-actions">
                            @if($isAdministrator)
                                <span class="user-type-protected"><i class="bi bi-shield-lock me-1"></i>Protegido</span>
                            @elseif(!$isIdentityProtected)
                                <a class="btn btn-sm btn-outline-success" href="{{ route('user-types.permissions', $type) }}" title="Configurar permisos" aria-label="Configurar permisos de {{ $type->name }}"><i class="bi bi-shield-check"></i></a>
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#editType{{ $type->id }}" title="Editar" aria-label="Editar {{ $type->name }}"><i class="bi bi-pencil-square"></i></button>
                            @endif
                            @if(!$isAdministrator && $type->users_count === 0)
                                <form method="post" action="{{ route('user-types.destroy', $type) }}" onsubmit="return confirm('¿Eliminar este tipo de usuario?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar" aria-label="Eliminar {{ $type->name }}"><i class="bi bi-trash3"></i></button></form>
                            @elseif(!$isAdministrator)
                                <span class="user-type-delete-hint" title="Reasigna primero a los usuarios de este tipo"><button class="btn btn-sm btn-outline-secondary" type="button" disabled aria-label="No se puede eliminar; reasigna primero los usuarios"><i class="bi bi-trash3"></i></button></span>
                            @endif
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="user-types-empty">No hay tipos de usuario registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @foreach($types as $type)
        @if(!$type->is_admin && !in_array($type->slug, ['administrador', 'pendiente-de-asignacion'], true))
            <div class="modal fade" id="editType{{ $type->id }}" tabindex="-1" aria-labelledby="editTypeLabel{{ $type->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                    <form method="post" action="{{ route('user-types.update', $type) }}">
                        @csrf @method('PUT')
                        <div class="modal-header"><h2 class="modal-title fs-5" id="editTypeLabel{{ $type->id }}">Editar tipo de usuario</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
                        <div class="modal-body"><div class="mb-3"><label class="form-label" for="typeName{{ $type->id }}">Nombre</label><input class="form-control" id="typeName{{ $type->id }}" name="name" value="{{ $type->name }}" required maxlength="100"></div><div class="mb-3"><label class="form-label" for="typeDescription{{ $type->id }}">Descripción</label><textarea class="form-control" id="typeDescription{{ $type->id }}" name="description" rows="3" maxlength="1000">{{ $type->description }}</textarea></div><input type="hidden" name="is_active" value="{{ $type->is_active ? 1 : 0 }}"></div>
                        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar cambios</button></div>
                    </form>
                </div></div>
            </div>
        @endif
    @endforeach
</div>
@stop
