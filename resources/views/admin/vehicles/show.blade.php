<div class="modal-header vehicle-modal-header">
    <div class="vehicle-modal-heading">
        <span class="vehicle-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0">Detalle de vehículo</h5>
            <small>Información de solo lectura</small>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body vehicle-modal-body">
    <div class="row g-3">
        <div class="col-12 col-lg-4">
            <div class="vehicle-detail-card h-100">
                <small class="vehicle-detail-label">Fotografía principal</small>
                @php
                    $imageUrl = $profileImage
                        ? asset('storage/'.$profileImage->imagen)
                        : asset('storage/images/no_logo.png');
                @endphp
                <button type="button" class="vehicle-show-image-preview" data-image="{{ $imageUrl }}"
                    data-vehicle="{{ $vehicle->name }}" title="Ampliar fotografía"
                    aria-label="Ampliar fotografía del vehículo">
                    <img src="{{ $imageUrl }}" alt="Fotografía principal de {{ $vehicle->name }}">
                </button>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Código</small><span class="vehicle-detail-value text-break">{{ $vehicle->code }}</span></div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Nombre</small><span class="vehicle-detail-value text-break">{{ $vehicle->name }}</span></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Placa</small><span class="vehicle-detail-value">{{ $vehicle->plate }}</span></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Año</small><span class="vehicle-detail-value">{{ $vehicle->year }}</span></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Tipo</small><span class="vehicle-detail-value text-break">{{ $vehicle->type?->name ?: 'Sin tipo' }}</span></div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Color</small><span class="vehicle-detail-value text-break">{{ $vehicle->color?->name ?: 'Sin color' }}</span></div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Marca</small><span class="vehicle-detail-value text-break">{{ $vehicle->brand?->name ?: 'Sin marca' }}</span></div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="vehicle-detail-card"><small class="vehicle-detail-label">Modelo</small><span class="vehicle-detail-value text-break">{{ $vehicle->model?->name ?: 'Sin modelo' }}</span></div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Carga</small><span class="vehicle-detail-value">{{ $vehicle->load_capacity }} Tn</span></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Compactación</small><span class="vehicle-detail-value">{{ $vehicle->compact_capacity }} Tn</span></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Combustible</small><span class="vehicle-detail-value">{{ $vehicle->fuel_capacity }} L</span></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Personas</small><span class="vehicle-detail-value">{{ $vehicle->occupats_capacity }}</span></div>
        </div>
        <div class="col-12">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Descripción</small><div class="vehicle-detail-description text-break">{{ $vehicle->description ?: 'Sin descripción' }}</div></div>
        </div>
        <div class="col-12 col-md-6">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Creación</small><span class="vehicle-detail-value">{{ $vehicle->created_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span></div>
        </div>
        <div class="col-12 col-md-6">
            <div class="vehicle-detail-card"><small class="vehicle-detail-label">Última actualización</small><span class="vehicle-detail-value">{{ $vehicle->updated_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</span></div>
        </div>
    </div>
</div>

<div class="modal-footer vehicle-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
