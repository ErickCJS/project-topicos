<div class="modal-header employee-type-modal-header">
    <div class="employee-type-modal-heading">
        <span class="employee-type-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0" id="employeeTypeShowModalLabel">Detalle del tipo de personal</h5>
            <small>Información de solo lectura</small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body employee-type-modal-body">
    <div class="row g-3">
        <div class="col-12">
            <div class="employee-type-detail-card">
                <small class="employee-type-detail-label">Nombre</small>
                <span class="employee-type-detail-value text-break">{{ $type->name }}</span>
            </div>
        </div>
        <div class="col-12">
            <div class="employee-type-detail-card">
                <small class="employee-type-detail-label">Descripción</small>
                <div class="employee-type-detail-description text-break">{{ $type->description ?: 'Sin descripción' }}</div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="employee-type-detail-card">
                <small class="employee-type-detail-label">Creación</small>
                <span class="employee-type-detail-value">{{ $type->created_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="employee-type-detail-card">
                <small class="employee-type-detail-label">Última actualización</small>
                <span class="employee-type-detail-value">{{ $type->updated_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer employee-type-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
