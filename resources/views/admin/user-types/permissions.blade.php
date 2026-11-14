@extends('adminlte::page')
@section('title', 'Permisos del tipo | RSU')
@section('content')
<div class="users-module"><div class="d-flex justify-content-between align-items-center mb-3"><h4>Permisos: {{ $type->name }}</h4><a class="btn btn-light border" href="{{ route('user-types.index') }}">Volver</a></div>
<form method="post" action="{{ route('user-types.permissions.save', $type) }}">@csrf @method('PUT')
@foreach($modules as $parent)<section class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center"><strong><i class="{{ $parent->icon ?: 'bi bi-grid' }} me-2"></i>{{ $parent->name }}</strong><label class="form-check mb-0"><input class="form-check-input permission-select-all" type="checkbox" data-scope="module-{{ $parent->id }}"><span class="form-check-label">Seleccionar módulo</span></label></div><div class="card-body"><div class="row g-3">
@foreach($parent->children as $child)<div class="col-12 col-xl-6"><div class="permission-card"><div class="d-flex justify-content-between align-items-center mb-2"><h6 class="mb-0">{{ $child->name }}</h6><label class="form-check mb-0"><input class="form-check-input permission-select-all" type="checkbox" data-scope="submodule-{{ $child->id }}"><span class="form-check-label">Seleccionar</span></label></div><div class="d-flex flex-wrap gap-3">
@foreach($child->permissions as $permission)<label class="form-check"><input class="form-check-input permission-option module-{{ $parent->id }} submodule-{{ $child->id }}" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, $assigned))><span class="form-check-label">{{ $permission->name }}</span></label>@endforeach
</div></div></div>@endforeach
</div></div></section>@endforeach
<div class="d-flex gap-2"><button class="btn btn-success">Guardar permisos</button><a href="{{ route('user-types.index') }}" class="btn btn-light border">Cancelar</a></div>
</form></div>
@section('js')
<script>
document.querySelectorAll('.permission-select-all').forEach(function (control) {
    control.addEventListener('change', function () {
        document.querySelectorAll('.' + this.dataset.scope).forEach(function (permission) { permission.checked = control.checked; });
    });
});
</script>
@stop
@stop
