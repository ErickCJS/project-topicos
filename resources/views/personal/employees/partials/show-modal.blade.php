<div class="modal-header text-white" style="background:#075985">
    <div>
        <h5 class="modal-title fw-semibold mb-0">Ficha del personal</h5>
        <small class="text-white-50">Información del empleado</small>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
<div class="modal-body bg-light p-3 p-md-4">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex flex-column flex-sm-row align-items-center gap-3 gap-md-4">
            <img src="{{ $employee->profile_photo_path ? asset('storage/'.$employee->profile_photo_path) : asset('storage/images/no_logo.png') }}" onerror="this.onerror=null;this.src='{{ asset('storage/images/no_logo.png') }}'" alt="Foto de {{ $employee->first_name }}" class="employee-show-photo rounded-circle border shadow-sm" style="width:132px;height:132px;object-fit:cover;flex:0 0 auto">
            <div class="text-center text-sm-start">
                <h4 class="fw-bold text-dark mb-1">{{ $employee->first_name }} {{ $employee->last_name }}</h4>
                <div class="text-muted mb-2">{{ $employee->employeeType?->name ?? 'Sin tipo asignado' }}</div>
                <span class="badge {{ $employee->status === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $employee->status }}</span>
                <span class="ms-2 text-muted">DNI {{ $employee->dni }}</span>
            </div>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-6 col-md-4"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">ID</small><span class="fw-semibold">{{ $employee->id }}</span></div></div>
        <div class="col-6 col-md-4"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Fecha de nacimiento</small><span class="fw-semibold">{{ $employee->birthdate ?: '—' }}</span></div></div>
        <div class="col-12 col-md-4"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Correo electrónico</small><span class="fw-semibold text-break">{{ $employee->email }}</span></div></div>
        <div class="col-12 col-md-6"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Dirección</small><span class="fw-semibold">{{ $employee->address ?: '—' }}</span></div></div>
        <div class="col-6 col-md-3"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Teléfono</small><span class="fw-semibold">{{ $employee->phone ?: '—' }}</span></div></div>
        <div class="col-6 col-md-3"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Licencia</small><span class="fw-semibold">{{ $employee->license ?: '—' }}</span></div></div>
        <div class="col-12 col-md-6"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Creación</small><span class="fw-semibold">{{ $employee->created_at?->format('d/m/Y H:i') ?? '—' }}</span></div></div>
        <div class="col-12 col-md-6"><div class="bg-white border rounded p-3 h-100"><small class="d-block text-muted mb-1">Última actualización</small><span class="fw-semibold">{{ $employee->updated_at?->format('d/m/Y H:i') ?? '—' }}</span></div></div>
    </div>
</div>
<div class="modal-footer bg-white"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
