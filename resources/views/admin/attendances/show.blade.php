<div class="modal-header attendance-modal-header">
    <div class="attendance-modal-heading">
        <span class="attendance-modal-icon" aria-hidden="true">
            <i class="bi bi-eye"></i>
        </span>
        <div>
            <h5 class="modal-title mb-0">Detalle de marcación</h5>
            <small class="text-muted">Registro individual de asistencia</small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body attendance-modal-body">
    <div class="row g-3">
        <div class="col-12">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Trabajador</small>
                <span class="attendance-detail-value">{{ $employee->dni }} - {{ $employee->first_name }} {{ $employee->last_name }}</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Tipo de marcación</small>
                <div>
                    <span class="badge {{ $attendance->type === 'Entrada' ? 'bg-success' : 'bg-info text-dark' }}">
                        {{ $attendance->type }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Fecha</small>
                <span class="attendance-detail-value">{{ $attendance->date->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Hora registrada</small>
                <span class="attendance-detail-value">{{ substr($attendance->time, 0, 5) }}</span>
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
                <small class="attendance-detail-label">Estado</small>
                <span class="badge {{ $attendance->status === 'Presente' ? 'bg-success' : ($attendance->status === 'Tardanza' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                    {{ $attendance->status }}
                </span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Origen</small>
                <span class="badge {{ $attendance->origin === 'Programado' ? 'bg-primary' : ($attendance->origin === 'Retén' ? 'bg-info text-dark' : 'bg-secondary') }}">
                    {{ $attendance->origin ?? 'No programado' }}
                </span>
            </div>
        </div>
        <div class="col-12">
            <div class="attendance-detail-card">
                <small class="attendance-detail-label">Observaciones</small>
                <span class="attendance-detail-value text-break">{{ $attendance->notes ?: 'Sin observaciones' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer attendance-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
