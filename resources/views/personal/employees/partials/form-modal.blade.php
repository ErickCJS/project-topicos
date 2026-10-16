@php($editing = isset($employee))
<form action="{{ $editing ? route('personal.employees.update', $employee) : route('personal.employees.store') }}" method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
    @csrf
    @if($editing) @method('PUT') @endif
    <div class="modal-header text-white" style="background:#075985">
        <h5 class="modal-title"><i class="bi bi-person-fill me-2"></i>{{ $editing ? 'Editar Personal' : 'Registrar Personal' }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">DNI <span class="text-danger">*</span></label><input class="form-control" id="employeeDocument" name="dni" value="{{ old('dni', $employee->dni ?? '') }}" maxlength="8" inputmode="numeric" pattern="[0-9]{8}" placeholder="8 dígitos" required><small class="text-muted">Ingrese exactamente 8 dígitos numéricos.</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Tipo de Personal <span class="text-danger">*</span></label><select class="form-select" name="tipo_personal_id" required><option value="">Seleccione un tipo</option>@foreach($tipos as $tipo)<option value="{{ $tipo->id }}" data-requiere-licencia="{{ str_contains(strtolower($tipo->name), 'conductor') || str_contains(strtolower($tipo->name), 'chofer') ? '1' : '0' }}" @selected(old('tipo_personal_id', $employee->employee_type_id ?? '') == $tipo->id)>{{ $tipo->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label><input class="form-control" name="nombres" value="{{ old('nombres', $employee->first_name ?? '') }}" placeholder="Ingrese los nombres" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label><input class="form-control" name="apellidos" value="{{ old('apellidos', $employee->last_name ?? '') }}" placeholder="Ingrese los apellidos" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Fecha de Nacimiento <span class="text-danger">*</span></label><input class="form-control" type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', isset($employee) ? substr($employee->birthdate, 0, 10) : '') }}" max="{{ now()->subYears(18)->format('Y-m-d') }}" required><small class="text-muted">Mayor de 18 años</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Teléfono</label><input class="form-control" name="telefono" value="{{ old('telefono', $employee->phone ?? '') }}" maxlength="9" inputmode="numeric" placeholder="987654321"><small class="text-muted">Número de teléfono (opcional)</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Email <span class="text-danger">*</span></label><input class="form-control" type="email" name="email" value="{{ old('email', $employee->email ?? '') }}" placeholder="personal@ejemplo.com" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Estado <span class="text-danger">*</span></label><select class="form-select" name="estado" {{ $editing ? '' : 'disabled' }} required><option value="Activo" @selected(old('estado', $employee->status ?? 'Activo') === 'Activo')>Activo</option>@if($editing)<option value="Inactivo" @selected(old('estado', $employee->status) === 'Inactivo')>Inactivo</option>@endif</select>@unless($editing)<input type="hidden" name="estado" value="Activo">@endunless</div>
                    <div class="col-md-6"><div class="d-flex align-items-center justify-content-between gap-2"><label class="form-label fw-semibold mb-2">{{ $editing ? 'Nueva contraseña' : 'Contraseña' }} @if(!$editing)<span class="text-danger">*</span>@endif</label>@if($editing)<label class="form-check mb-2"><input class="form-check-input" type="checkbox" id="employeeChangePassword"><span class="form-check-label">Cambiar</span></label>@endif</div><input class="form-control" id="employeePassword" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres" {{ $editing ? 'disabled' : 'required' }}><small class="text-muted">Mínimo 8 caracteres</small></div>
                    <div class="col-md-12"><label class="form-label fw-semibold">Dirección <span class="text-danger">*</span></label><input class="form-control" name="direccion" value="{{ old('direccion', $employee->address ?? '') }}" placeholder="Av. Principal 123, Distrito, Ciudad" required><small class="text-muted">Dirección completa</small></div>
                    <div class="col-md-6" data-campo-licencia hidden><label class="form-label fw-semibold">Número de licencia <span class="text-danger">*</span></label><input class="form-control" name="licencia" value="{{ old('licencia', $employee->license ?? '') }}" maxlength="9" pattern="[A-Za-z][0-9]{8}" placeholder="Q22413362" disabled><small class="text-muted">Inicie con una letra seguida exactamente por el DNI.</small></div>
                </div>
            </div>
            <div class="col-lg-4">
                <label class="form-label fw-semibold">Foto de Perfil @if(!$editing)<span class="text-danger">*</span>@endif</label>
                <label for="employeeProfilePhoto" class="d-flex flex-column align-items-center justify-content-center border rounded bg-light w-100" style="height:306px;cursor:pointer;overflow:hidden">
                    <i class="bi bi-card-image text-secondary {{ $editing && $employee->profile_photo_path ? 'd-none' : '' }}" id="employeePhotoPlaceholder" style="font-size:8rem"></i>
                    <img id="employeePhotoPreview" alt="Vista previa de la foto" class="w-100 h-100 {{ $editing && $employee->profile_photo_path ? '' : 'd-none' }}" src="{{ $editing && $employee->profile_photo_path ? asset('storage/'.$employee->profile_photo_path) : '' }}" onerror="this.classList.add('d-none');document.getElementById('employeePhotoPlaceholder').classList.remove('d-none')" style="object-fit:cover">
                </label>
                <input class="form-control d-none" type="file" id="employeeProfilePhoto" name="foto" accept="image/*" {{ $editing ? '' : 'required' }}>
                <small class="text-muted d-block text-center mt-2">Haga clic para seleccionar una imagen</small>
            </div>
        </div>
    </div>
    <div class="modal-footer d-flex justify-content-end gap-2">
        @if($editing)
            <button class="btn btn-success" type="submit"><i class="bi bi-floppy me-1"></i>Actualizar</button>
        @else
            <button class="btn btn-success" type="submit"><i class="bi bi-floppy me-1"></i>Registrar</button>
        @endif
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button>
    </div>
</form>
