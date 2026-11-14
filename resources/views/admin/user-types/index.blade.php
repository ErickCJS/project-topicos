@extends('adminlte::page')
@section('title', 'Tipos de usuario | RSU')
@section('content')
<div class="users-module"><h4 class="mb-3"><i class="bi bi-person-badge me-2"></i>Tipos de usuario</h4>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="card mb-3"><div class="card-header">Crear tipo de usuario</div><div class="card-body"><form method="post" action="{{ route('user-types.store') }}" class="row g-3">@csrf<div class="col-md-4"><label class="form-label">Nombre</label><input class="form-control" name="name" required maxlength="100"></div><div class="col-md-6"><label class="form-label">Descripción</label><input class="form-control" name="description"></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-success">Crear tipo</button></div></form></div></div>
<div class="card"><div class="table-responsive"><table class="table table-striped align-middle mb-0"><thead><tr><th>Nombre</th><th>Descripción</th><th>Usuarios asignados</th><th>Estado</th><th>Última actualización</th><th>Acciones</th></tr></thead><tbody>
@foreach($types as $type)<tr><td>{{ $type->name }}</td><td>{{ $type->description ?: '—' }}</td><td>{{ $type->users_count }}</td><td>{{ $type->is_active ? 'Activo' : 'Inactivo' }}</td><td>{{ $type->updated_at?->format('d/m/Y H:i') ?: '—' }}</td><td class="d-flex gap-2">
@if(!$type->is_admin && !in_array($type->slug, ['administrador', 'pendiente-de-asignacion'], true))<a class="btn btn-sm btn-outline-primary" href="{{ route('user-types.permissions', $type) }}">Configurar permisos</a>@endif
@if(!$type->is_system)<form method="post" action="{{ route('user-types.update', $type) }}" class="d-flex gap-1">@csrf @method('PUT')<input class="form-control form-control-sm" name="name" value="{{ $type->name }}" required><input type="hidden" name="description" value="{{ $type->description }}"><input type="hidden" name="is_active" value="{{ $type->is_active ? 1 : 0 }}"><button class="btn btn-sm btn-outline-warning" aria-label="Guardar nombre"><i class="bi bi-floppy"></i></button></form>@endif
@if(!$type->is_system && $type->users_count === 0)<form method="post" action="{{ route('user-types.destroy', $type) }}" onsubmit="return confirm('¿Desea eliminar este tipo de usuario?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" aria-label="Eliminar tipo"><i class="bi bi-trash"></i></button></form>@endif
</td></tr>@endforeach
</tbody></table></div></div></div>
@stop
