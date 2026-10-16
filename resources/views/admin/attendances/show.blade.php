<div class="modal-header attendance-modal-header">
    <div class="attendance-modal-heading">
        <span class="attendance-modal-icon" aria-hidden="true">
            <i class="bi bi-eye"></i>
        </span>
        <div>
            <h5 class="modal-title mb-0">Detalle de asistencia</h5>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body attendance-modal-body">
    <div class="row g-3">
        <div class="col-12">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Empleado</small>
                <span class="attendance-detail-value">{{ $employee->dni }} - {{ $employee->first_name }} {{ $employee->last_name }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Turno</small>
                <span class="attendance-detail-value">{{ $shift->name }}</span>
                <div class="attendance-detail-secondary">{{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Fecha</small>
                <span class="attendance-detail-value">{{ $attendance->date->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Hora</small>
                <span class="attendance-detail-value">{{ substr($attendance->time, 0, 5) }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Tipo</small>
                <span class="attendance-detail-value">{{ $attendance->type }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Estado</small>
                <span class="badge {{ $attendance->status === 'Presente' ? 'bg-success' : 'bg-secondary' }}">{{ $attendance->status }}</span>
            </div>
        </div>
        <div class="col-12">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Notas</small>
                <span class="attendance-detail-value text-break">{{ $attendance->notes ?: 'Sin notas' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer attendance-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
