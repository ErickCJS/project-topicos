<div class="modal-header model-modal-header">
    <div class="model-modal-heading">
        <span class="model-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0">Detalle de modelo</h5>
            <small>Información de solo lectura</small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body model-modal-body">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="model-detail-card">
                <small class="model-detail-label">Marca</small>
                <span class="model-detail-value text-break">{{ $model->brand?->name ?: 'Sin marca' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="model-detail-card">
                <small class="model-detail-label">Nombre</small>
                <span class="model-detail-value text-break">{{ $model->name }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="model-detail-card">
                <small class="model-detail-label">Código</small>
                <span class="model-detail-value text-break">{{ $model->code ?: 'Sin código' }}</span>
            </div>
        </div>
        <div class="col-12">
            <div class="model-detail-card">
                <small class="model-detail-label">Descripción</small>
                <div class="model-detail-description text-break">{{ $model->description ?: 'Sin descripción' }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="model-detail-card">
                <small class="model-detail-label">Creación</small>
                <span class="model-detail-value">{{ $model->created_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="model-detail-card">
                <small class="model-detail-label">Última actualización</small>
                <span class="model-detail-value">{{ $model->updated_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer model-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
