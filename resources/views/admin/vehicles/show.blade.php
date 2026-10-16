<div class="modal-header vehicle-modal-header">
    <div class="vehicle-modal-heading">
        <span class="vehicle-modal-icon" aria-hidden="true"><i class="bi bi-eye"></i></span>
        <div>
            <h5 class="modal-title mb-0">Detalle de vehículo</h5>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
</div>

<div class="modal-body vehicle-modal-body">
    @php
        $imageUrl = $profileImage
            ? asset('storage/'.$profileImage->imagen)
            : asset('storage/images/no_logo.png');
    @endphp

    <div class="row g-4 align-items-start vehicle-show-layout">
        <div class="col-12 col-lg-8">
            <div class="vehicle-detail-card p-0 overflow-hidden">
                <table class="table table-sm align-middle mb-0 vehicle-detail-table">
                    <tbody>
                        <tr><th scope="row">Código</th><td class="text-break">{{ $vehicle->code }}</td></tr>
                        <tr><th scope="row">Nombre</th><td class="text-break">{{ $vehicle->name }}</td></tr>
                        <tr><th scope="row">Placa</th><td class="text-break">{{ $vehicle->plate }}</td></tr>
                        <tr><th scope="row">Año</th><td>{{ $vehicle->year }}</td></tr>
                        <tr><th scope="row">Marca</th><td class="text-break">{{ $vehicle->brand?->name ?: 'Sin marca' }}</td></tr>
                        <tr><th scope="row">Modelo</th><td class="text-break">{{ $vehicle->model?->name ?: 'Sin modelo' }}</td></tr>
                        <tr><th scope="row">Tipo</th><td class="text-break">{{ $vehicle->type?->name ?: 'Sin tipo' }}</td></tr>
                        <tr><th scope="row">Color</th><td class="text-break">{{ $vehicle->color?->name ?: 'Sin color' }}</td></tr>
                        <tr><th scope="row">Capacidad de carga</th><td>{{ $vehicle->load_capacity }} Tn</td></tr>
                        <tr><th scope="row">Compactación</th><td>{{ $vehicle->compact_capacity }} Tn</td></tr>
                        <tr><th scope="row">Combustible</th><td>{{ $vehicle->fuel_capacity }} L</td></tr>
                        <tr><th scope="row">Personas</th><td>{{ $vehicle->occupats_capacity }}</td></tr>
                        <tr><th scope="row">Descripción</th><td class="text-break">{{ $vehicle->description ?: 'Sin descripción' }}</td></tr>
                        <tr><th scope="row">Creación</th><td>{{ $vehicle->created_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</td></tr>
                        <tr><th scope="row">Última actualización</th><td>{{ $vehicle->updated_at?->format('d/m/Y H:i:s') ?: 'Sin fecha' }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="vehicle-detail-card vehicle-show-photo-card">
                <small class="vehicle-detail-label">Fotografía principal</small>
                <button type="button" class="vehicle-show-image-preview" data-image="{{ $imageUrl }}"
                    data-vehicle="{{ $vehicle->name }}" title="Ampliar fotografía"
                    aria-label="Ampliar fotografía del vehículo">
                    <img src="{{ $imageUrl }}" alt="Fotografía principal de {{ $vehicle->name }}">
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer vehicle-modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
</div>
