<div class="modal-header brand-modal-header">
    <div class="brand-modal-heading">
        <span class="brand-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0">Detalle de marca</h5>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body brand-modal-body">
    <p class="small text-muted mb-3">Información de solo lectura</p>
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="brand-detail-card">
                <small class="brand-detail-label">Logo</small>
                <div class="brand-detail-logo-frame">
                    <button type="button" class="brand-logo-preview" data-image="{{ $logoUrl }}"
                        data-brand="{{ $brand->name }}" title="Ampliar logo de {{ $brand->name }}"
                        aria-label="Ampliar logo de {{ $brand->name }}">
                        <img class="brand-detail-logo" src="{{ $logoUrl }}" alt="Logo de {{ $brand->name }}">
                    </button>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="row g-3">
                <div class="col-12">
                    <div class="brand-detail-card">
                        <small class="brand-detail-label">Nombre</small>
                        <span class="brand-detail-value text-break">{{ $brand->name }}</span>
                    </div>
                </div>
                <div class="col-12">
                    <div class="brand-detail-card">
                        <small class="brand-detail-label">Descripción</small>
                        <div class="brand-detail-description text-break">{{ $brand->description ?: 'Sin descripción' }}</div>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="brand-detail-card">
                        <small class="brand-detail-label">Creación</small>
                        <span class="brand-detail-value">{{ $brand->created_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span>
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="brand-detail-card">
                        <small class="brand-detail-label">Última actualización</small>
                        <span class="brand-detail-value">{{ $brand->updated_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer brand-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
