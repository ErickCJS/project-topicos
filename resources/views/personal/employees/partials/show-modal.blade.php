<div class="modal-header employee-modal-header">
    <div class="employee-modal-heading">
        <span class="employee-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0" id="employeeShowModalLabel">Ficha del personal</h5>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body employee-modal-body">
    <div class="employee-show-profile p-3 mb-4">
        <div class="d-flex flex-column flex-sm-row align-items-center gap-3 gap-md-4">
            @php
                $photoPath = trim((string) $employee->profile_photo_path);
                $employeePhotoPlaceholder = 'data:image/svg+xml,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48"><rect width="48" height="48" rx="24" fill="#e9ecef"/><circle cx="24" cy="18" r="8" fill="#adb5bd"/><path d="M8 43c1-9 7-14 16-14s15 5 16 14" fill="#adb5bd"/></svg>');
                $employeePhoto = $photoPath !== '' && ! preg_match('/^https?:\/\//i', $photoPath)
                    ? asset('storage/'.ltrim($photoPath, '/'))
                    : $employeePhotoPlaceholder;
            @endphp
            <img src="{{ $employeePhoto }}"
                onerror="this.onerror=null;this.src='{{ $employeePhotoPlaceholder }}'"
                alt="Foto de {{ $employee->first_name }}"
                class="employee-show-photo rounded-circle border shadow-sm">
            <div class="text-center text-sm-start">
                <h4 class="fw-bold mb-1">{{ $employee->first_name }} {{ $employee->last_name }}</h4>
                <div class="mb-2" style="color:var(--bs-secondary-color)">
                    {{ $employee->employeeType?->name ?? 'Sin tipo asignado' }}
                </div>
                <span class="badge {{ $employee->status === 'Activo' ? 'bg-success' : 'bg-secondary' }}">{{ $employee->status }}</span>
                <span class="ms-2" style="color:var(--bs-secondary-color)">DNI {{ $employee->dni }}</span>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-6 col-md-4">
            <div class="employee-detail-card">
                <small class="employee-detail-label">ID</small>
                <span class="employee-detail-value">{{ $employee->id }}</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Fecha de nacimiento</small>
                <span class="employee-detail-value">{{ $employee->birthdate ?: '—' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Correo electrónico</small>
                <span class="employee-detail-value text-break">{{ $employee->email }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Dirección</small>
                <span class="employee-detail-value text-break">{{ $employee->address ?: '—' }}</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Teléfono</small>
                <span class="employee-detail-value">{{ $employee->phone ?: '—' }}</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Licencia</small>
                <span class="employee-detail-value">{{ $employee->license ?: '—' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Creación</small>
                <span class="employee-detail-value">{{ $employee->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="employee-detail-card">
                <small class="employee-detail-label">Última actualización</small>
                <span class="employee-detail-value">{{ $employee->updated_at?->format('d/m/Y H:i') ?? '—' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer employee-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
