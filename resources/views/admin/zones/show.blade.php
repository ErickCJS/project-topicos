<div class="modal-header zone-modal-header">
    <div class="zone-modal-heading">
        <span class="zone-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div><h5 class="modal-title mb-0">Detalle de zona</h5></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>
<div class="modal-body zone-modal-body">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="zone-detail-card"><small class="zone-detail-label">Nombre</small><span class="zone-detail-value">{{ $zone->name }}</span></div>
        </div>
        <div class="col-12 col-md-6">
            <div class="zone-detail-card"><small class="zone-detail-label">Distrito</small><span class="zone-detail-value">{{ $zone->district?->name ?? 'Sin distrito' }}</span></div>
        </div>
        <div class="col-12 col-md-6">
            <div class="zone-detail-card"><small class="zone-detail-label">Provincia</small><span class="zone-detail-value">{{ $zone->district?->province?->name ?? 'Sin provincia' }}</span></div>
        </div>
        <div class="col-12 col-md-6">
            <div class="zone-detail-card"><small class="zone-detail-label">Departamento</small><span class="zone-detail-value">{{ $zone->district?->province?->department?->name ?? 'Sin departamento' }}</span></div>
        </div>
        <div class="col-12">
            <div class="zone-detail-card"><small class="zone-detail-label">Descripción</small><span class="zone-detail-value text-break">{{ $zone->description ?: 'Sin descripción' }}</span></div>
        </div>
        <div class="col-12">
            <small class="zone-detail-label">Perímetro</small>
            <div class="zone-map zone-map-readonly mt-2" data-zone-show-map data-perimeter="{{ e(json_encode($zone->perimeter, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"></div>
        </div>
    </div>
</div>
<div class="modal-footer zone-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
