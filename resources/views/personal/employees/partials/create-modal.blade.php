<form action="{{ route('personal.employees.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    <div class="modal-header text-white" style="background:#075985">
        <h5 class="modal-title"><i class="bi bi-person-plus-fill me-2"></i>Nuevo Personal</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
    </div>
    <div class="modal-body p-4">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">DNI <span class="text-danger">*</span></label><input class="form-control" name="dni" maxlength="8" minlength="8" placeholder="12345678" required><small class="text-muted">8 dígitos únicos</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Tipo de Personal <span class="text-danger">*</span></label><select class="form-select" name="tipo_personal_id" required><option value="">Seleccione un tipo</option>@foreach($tipos as $tipo)<option value="{{ $tipo->id }}" data-requiere-licencia="{{ str_contains(strtolower($tipo->name), 'conductor') || str_contains(strtolower($tipo->name), 'chofer') ? '1' : '0' }}">{{ $tipo->name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Nombres <span class="text-danger">*</span></label><input class="form-control" name="nombres" placeholder="Ingrese los nombres" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Apellidos <span class="text-danger">*</span></label><input class="form-control" name="apellidos" placeholder="Ingrese los apellidos" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Fecha de Nacimiento <span class="text-danger">*</span></label><input class="form-control" type="date" name="fecha_nacimiento" max="{{ now()->subYears(18)->format('Y-m-d') }}" required><small class="text-muted">Mayor de 18 años</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Teléfono</label><input class="form-control" name="telefono" maxlength="15" placeholder="987654321"><small class="text-muted">Número de teléfono (opcional)</small></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Email <span class="text-danger">*</span></label><input class="form-control" type="email" name="email" placeholder="personal@ejemplo.com" required></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Estado</label><input class="form-control" type="text" value="Activo" readonly aria-readonly="true"><input type="hidden" name="estado" value="Activo"></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Contraseña <span class="text-danger">*</span></label><input class="form-control" type="password" name="password" minlength="8" placeholder="Mínimo 8 caracteres" required><small class="text-muted">Mínimo 8 caracteres</small></div>
                    <div class="col-md-12"><label class="form-label fw-semibold">Dirección <span class="text-danger">*</span></label><input class="form-control" name="direccion" minlength="10" placeholder="Av. Principal 123, Distrito, Ciudad" required><small class="text-muted">Dirección completa (mínimo 10 caracteres)</small></div>
                    <div class="col-md-6" data-campo-licencia hidden><label class="form-label fw-semibold">Número de licencia <span class="text-danger">*</span></label><input class="form-control" name="licencia" maxlength="20" disabled></div>
                </div>
            </div>
            <div class="col-lg-5">
                <label class="form-label fw-semibold">Foto de Perfil <span class="text-danger">*</span></label>
                <label for="employeeProfilePhoto" class="d-flex flex-column align-items-center justify-content-center border rounded bg-light w-100" style="height:306px;cursor:pointer;overflow:hidden">
                    <i class="bi bi-card-image text-secondary" id="employeePhotoPlaceholder" style="font-size:8rem"></i>
                    <img id="employeePhotoPreview" alt="Vista previa de la foto" class="w-100 h-100 d-none" style="object-fit:cover">
                </label>
                <input class="form-control d-none" type="file" id="employeeProfilePhoto" name="foto" accept="image/*" required>
                <small class="text-muted d-block text-center mt-2">Haga clic para seleccionar una imagen</small>
            </div>
        </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-danger" data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Cancelar</button><button class="btn btn-primary" type="submit"><i class="bi bi-floppy me-1"></i>Guardar</button></div>
</form>
