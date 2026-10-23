<div class="vehicle-images-manager" id="vehicleImagesManager" data-vehicle-id="{{ $vehicle_id }}">
    <div class="vehicle-images-upload">
        <div class="vehicle-images-upload__field">
            <label for="photos" class="form-label fw-semibold">Selecciona las imágenes</label>
            <small class="form-text">Formatos JPG, PNG o WEBP.</small>
            <input type="file" id="photos" accept="image/jpeg,image/png,image/webp" multiple class="form-control">
        </div>
        <button type="button" class="btn_subir btn btn-success">
            <i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i>Subir imágenes
        </button>
    </div>

    <div class="vehicle-images-divider" role="separator" aria-label="Imágenes registradas">
        <span>Imágenes registradas</span>
    </div>

    <div class="vehicle-photo-grid" id="preview-photos">

        @forelse ($photos as $photo)
            <x-card-vehicle
                :id="$photo->id"
                :url="asset('storage/' . $photo->imagen)"
                :profile="$photo->profile"
            />
        @empty
            <p class="vehicle-photo-empty">No hay imágenes registradas.</p>
        @endforelse
    </div>
</div>
