@php
    $status = $contract->statusLabel();
    $statusClass = match ($status) {
        'Vigente' => 'text-bg-success',
        'Por iniciar' => 'text-bg-info',
        'Anulado' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
@endphp

<div class="modal-header contract-modal-header">
    <div class="contract-modal-heading">
        <span class="contract-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0" id="contractShowModalLabel">Detalle del contrato</h5>
            <small>Información de solo lectura</small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body contract-modal-body">
    <div class="row g-3">
        <div class="col-12 col-md-8">
            <div class="contract-detail-card">
                <small class="contract-detail-label">Trabajador</small>
                <span class="contract-detail-value text-break">
                    {{ $contract->employee->first_name }} {{ $contract->employee->last_name }}
                </span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="contract-detail-card">
                <small class="contract-detail-label">DNI</small>
                <span class="contract-detail-value">{{ $contract->employee->dni }}</span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="contract-detail-card">
                <small class="contract-detail-label">Tipo</small>
                <span class="contract-detail-value">{{ $contract->type }}</span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="contract-detail-card">
                <small class="contract-detail-label">Sueldo</small>
                <span class="contract-detail-value">S/ {{ number_format((float) $contract->salary, 2) }}</span>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="contract-detail-card">
                <small class="contract-detail-label">Estado</small>
                <span class="badge {{ $statusClass }}">{{ $status }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="contract-detail-card">
                <small class="contract-detail-label">Fecha de inicio</small>
                <span class="contract-detail-value">{{ $contract->start_date->format('d/m/Y') }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="contract-detail-card">
                <small class="contract-detail-label">Fecha de término</small>
                <span class="contract-detail-value">{{ $contract->end_date?->format('d/m/Y') ?? 'Sin término' }}</span>
            </div>
        </div>
        @if($contract->cancelled_at)
            <div class="col-12">
                <div class="contract-detail-card">
                    <small class="contract-detail-label">Fecha de anulación</small>
                    <span class="contract-detail-value">{{ $contract->cancelled_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="modal-footer contract-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
