<form action="{{ route('personal.employees.update', $employee) }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf @method('PUT')
    <div class="modal-header"><h5 class="modal-title">Editar personal</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><div class="row g-3">
        <div class="col-md-6"><label class="form-label">DNI</label><input class="form-control" name="dni" value="{{ $employee->dni }}" required></div>
        <div class="col-md-6"><label class="form-label">Tipo de personal</label><select class="form-select" name="tipo_personal_id" required>@foreach($tipos as $tipo)<option value="{{ $tipo->id }}" data-requiere-licencia="{{ str_contains(strtolower($tipo->name), 'conductor') || str_contains(strtolower($tipo->name), 'chofer') ? '1' : '0' }}" @selected($employee->employee_type_id == $tipo->id)>{{ $tipo->name }}</option>@endforeach</select></div>
        <div class="col-md-6"><label class="form-label">Nombres</label><input class="form-control" name="nombres" value="{{ $employee->first_name }}" required></div>
        <div class="col-md-6"><label class="form-label">Apellidos</label><input class="form-control" name="apellidos" value="{{ $employee->last_name }}" required></div>
        <div class="col-md-6"><label class="form-label">Fecha de nacimiento</label><input class="form-control" type="date" name="fecha_nacimiento" value="{{ $employee->birthdate }}" required></div>
        <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ $employee->email }}" required></div>
        <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" name="estado" required><option @selected($employee->status === 'Activo')>Activo</option><option @selected($employee->status === 'Inactivo')>Inactivo</option></select></div>
        <div class="col-md-6"><label class="form-label">Nueva contraseña</label><input class="form-control" type="password" name="password"></div>
        <div class="col-md-6"><label class="form-label">Dirección</label><input class="form-control" name="direccion" value="{{ $employee->address }}" required></div>
        <div class="col-md-6"><label class="form-label">Foto</label><input class="form-control" type="file" name="foto" accept="image/*"></div>
        <div class="col-md-6"><label class="form-label">Teléfono</label><input class="form-control" name="telefono" value="{{ $employee->phone }}"></div>
        <div class="col-md-6" data-campo-licencia hidden><label class="form-label">Número de licencia <span class="text-danger">*</span></label><input class="form-control" name="licencia" value="{{ $employee->license }}" maxlength="20" disabled></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" type="submit">Actualizar</button></div>
</form>
