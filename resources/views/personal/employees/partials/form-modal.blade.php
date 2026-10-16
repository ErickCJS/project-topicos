@php($editing = isset($employee))
<form action="{{ $editing ? route('personal.employees.update', $employee) : route('personal.employees.store') }}"
    method="POST" enctype="multipart/form-data" autocomplete="off" novalidate>
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    <div class="modal-header employee-modal-header">
        <div class="employee-modal-heading">
            <span class="employee-modal-icon" aria-hidden="true"><i class="bi bi-person-vcard"></i></span>
            <div>
                <h5 class="modal-title mb-0" id="employeeFormModalLabel">
                    {{ $editing ? 'Editar empleado' : 'Registrar empleado' }}
                </h5>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>

    <div class="modal-body employee-modal-body">
        <p class="form-text mb-3">Los campos marcados con * son obligatorios.</p>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeDocument">DNI <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeDocument" name="dni"
                            value="{{ old('dni', $employee->dni ?? '') }}" maxlength="8" inputmode="numeric"
                            pattern="[0-9]{8}" placeholder="8 dígitos" required>
                        <div class="form-text">Ingrese exactamente 8 dígitos numéricos.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeType">Tipo de personal <span class="text-danger">*</span></label>
                        <select class="form-select" id="employeeType" name="tipo_personal_id" required>
                            <option value="">Seleccione un tipo</option>
                            @foreach($tipos as $tipo)
                                <option value="{{ $tipo->id }}"
                                    data-requiere-licencia="{{ str_contains(strtolower($tipo->name), 'conductor') || str_contains(strtolower($tipo->name), 'chofer') ? '1' : '0' }}"
                                    @selected(old('tipo_personal_id', $employee->employee_type_id ?? '') == $tipo->id)>
                                    {{ $tipo->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeFirstName">Nombres <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeFirstName" name="nombres"
                            value="{{ old('nombres', $employee->first_name ?? '') }}"
                            maxlength="100" placeholder="Ingrese los nombres" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeLastName">Apellidos <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeLastName" name="apellidos"
                            value="{{ old('apellidos', $employee->last_name ?? '') }}"
                            maxlength="100" placeholder="Ingrese los apellidos" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeBirthdate">Fecha de nacimiento <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeBirthdate" type="date" name="fecha_nacimiento"
                            value="{{ old('fecha_nacimiento', isset($employee) ? substr($employee->birthdate, 0, 10) : '') }}"
                            max="{{ now()->subYears(18)->format('Y-m-d') }}" required>
                        <div class="form-text">El empleado debe ser mayor de 18 años.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeePhone">Teléfono</label>
                        <input class="form-control" id="employeePhone" name="telefono"
                            value="{{ old('telefono', $employee->phone ?? '') }}"
                            maxlength="9" inputmode="numeric" placeholder="987654321">
                        <div class="form-text">Número de teléfono opcional.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeEmail">Correo electrónico <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeEmail" type="email" name="email"
                            value="{{ old('email', $employee->email ?? '') }}"
                            maxlength="150" placeholder="personal@ejemplo.com" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold" for="employeeStatus">Estado <span class="text-danger">*</span></label>
                        <select class="form-select" id="employeeStatus" name="estado" {{ $editing ? '' : 'disabled' }} required>
                            <option value="Activo" @selected(old('estado', $employee->status ?? 'Activo') === 'Activo')>Activo</option>
                            @if($editing)
                                <option value="Inactivo" @selected(old('estado', $employee->status) === 'Inactivo')>Inactivo</option>
                            @endif
                        </select>
                        @unless($editing)
                            <input type="hidden" name="estado" value="Activo">
                        @endunless
                    </div>

                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <label class="form-label fw-semibold mb-2" for="employeePassword">
                                {{ $editing ? 'Nueva contraseña' : 'Contraseña' }}
                                @unless($editing)<span class="text-danger">*</span>@endunless
                            </label>
                            @if($editing)
                                <label class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" id="employeeChangePassword">
                                    <span class="form-check-label">Cambiar</span>
                                </label>
                            @endif
                        </div>
                        <input class="form-control" id="employeePassword" type="password" name="password"
                            minlength="8" autocomplete="new-password" placeholder="Mínimo 8 caracteres"
                            {{ $editing ? 'disabled' : 'required' }}>
                        <div class="form-text">Mínimo 8 caracteres.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold" for="employeeAddress">Dirección <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeAddress" name="direccion"
                            value="{{ old('direccion', $employee->address ?? '') }}"
                            maxlength="255" placeholder="Av. Principal 123, Distrito, Ciudad" required>
                    </div>

                    <div class="col-12 col-md-6" data-campo-licencia hidden>
                        <label class="form-label fw-semibold" for="employeeLicense">Número de licencia <span class="text-danger">*</span></label>
                        <input class="form-control" id="employeeLicense" name="licencia"
                            value="{{ old('licencia', $employee->license ?? '') }}"
                            maxlength="9" pattern="[A-Za-z][0-9]{8}" placeholder="Q22413362" disabled>
                        <div class="form-text">Una letra seguida exactamente por los ocho dígitos del DNI.</div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4 employee-photo-column">
                <div class="employee-photo-panel">
                    <label class="form-label fw-semibold" for="employeeProfilePhoto">
                        Foto de perfil @unless($editing)<span class="text-danger">*</span>@endunless
                    </label>
                    <label for="employeeProfilePhoto" class="employee-photo-picker">
                        <i class="bi bi-card-image employee-photo-placeholder {{ $editing && $employee->profile_photo_path ? 'd-none' : '' }}"
                            id="employeePhotoPlaceholder" aria-hidden="true"></i>
                        <img id="employeePhotoPreview" alt="Vista previa de la foto"
                            class="employee-photo-preview {{ $editing && $employee->profile_photo_path ? '' : 'd-none' }}"
                            src="{{ $editing && $employee->profile_photo_path ? asset('storage/'.$employee->profile_photo_path) : '' }}"
                            onerror="this.classList.add('d-none');document.getElementById('employeePhotoPlaceholder').classList.remove('d-none')">
                    </label>
                    <input class="form-control d-none" type="file" id="employeeProfilePhoto" name="foto"
                        accept="image/*" {{ $editing ? '' : 'required' }}>
                    <div class="form-text text-center mt-2">Haga clic para seleccionar una imagen.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-footer employee-modal-footer">
        <button class="btn btn-success" type="submit">
            <i class="bi bi-floppy me-1" aria-hidden="true"></i>{{ $editing ? 'Actualizar' : 'Registrar' }}
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Cancelar
        </button>
    </div>
</form>
