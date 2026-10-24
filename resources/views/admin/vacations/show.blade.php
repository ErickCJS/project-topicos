@php
    $status = $vacation->statusLabel();
    $statusClass = match ($status) {
        'Aprobada' => 'text-bg-success',
        'Rechazada' => 'text-bg-secondary',
        default => 'text-bg-warning',
    };
@endphp

<div class="modal-header vacation-modal-header">
    <div class="vacation-modal-heading">
        <span class="vacation-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div><h5 class="modal-title mb-0" id="vacationShowModalLabel">Detalle de la vacación</h5></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body vacation-modal-body">
    @if($vacation->isHistorical())
        <div class="alert alert-warning" role="alert">
            Este registro histórico está <strong>pendiente de regularización</strong>. Sus días ya forman parte del saldo aprobado.
        </div>
    @endif
    @if($vacation->isRejected())
        <div class="alert alert-secondary" role="alert">
            <strong>Motivo del rechazo:</strong> {{ $vacation->rejection_reason ?: 'Sin motivo registrado.' }}
        </div>
    @endif
    <div class="row g-3">
        <div class="col-12 col-md-8">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Trabajador</small>
                <span class="vacation-detail-value">{{ $vacation->employee->first_name }} {{ $vacation->employee->last_name }}</span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">DNI</small>
                <span class="vacation-detail-value">{{ $vacation->employee->dni }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Contrato</small>
                <span class="vacation-detail-value">{{ $contractLabel }}</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Año</small>
                <span class="vacation-detail-value">{{ $vacation->year }}</span>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Estado</small>
                <span class="badge {{ $statusClass }}">{{ $status }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Fecha de inicio</small>
                <span class="vacation-detail-value">{{ $vacation->start_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Fecha de término</small>
                <span class="vacation-detail-value">{{ $vacation->end_date?->format('d/m/Y') ?? 'Sin fecha' }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Días programados</small>
                <span class="vacation-detail-value">{{ $vacation->scheduled_days }}</span>
            </div>
        </div>
        <div class="col-6">
            <div class="vacation-detail-card">
                <small class="vacation-detail-label">Saldo anual</small>
                <span class="vacation-detail-value">{{ $balance }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer vacation-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
