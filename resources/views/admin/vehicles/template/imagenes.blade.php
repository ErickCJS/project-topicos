<div class="vehicle-images-manager" id="vehicleImagesManager" data-vehicle-id="{{ $vehicle_id }}">
    <div class="vehicle-form-intro">
        <p class="small mb-0">Suba varias fotografías y seleccione cuál se mostrará como principal.</p>
    </div>

    <div class="mb-3">
        <label for="photos" class="form-label fw-semibold">Seleccionar imágenes</label>
        <input type="file" id="photos" accept="image/jpeg,image/png,image/webp" multiple class="form-control">
        <button type="button" class="btn_subir btn btn-success mt-2">
            <i class="bi bi-cloud-arrow-up me-1" aria-hidden="true"></i>Subir imágenes
        </button>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th>Imagen</th>
                    <th class="text-center">Principal</th>
                    <th class="text-center">Acción</th>
                </tr>
            </thead>
            <tbody id="preview-photos">
                @forelse ($photos as $photo)
                    <tr>
                        <td>
                            <button type="button" class="vehicle-photo-preview ampliar_foto"
                                data-image="{{ asset('storage/'.$photo->imagen) }}" title="Ampliar fotografía"
                                aria-label="Ampliar fotografía del vehículo">
                                <img src="{{ asset('storage/'.$photo->imagen) }}" alt="Foto del vehículo" width="80" height="80">
                            </button>
                        </td>
                        <td class="text-center">
                            <input type="radio" name="foto_principal" class="form-check-input radio_principal"
                                value="{{ $photo->id }}" {{ $photo->profile ? 'checked' : '' }}
                                aria-label="Seleccionar como fotografía principal">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm btn_eliminar_foto" data-id="{{ $photo->id }}"
                                title="Eliminar fotografía" aria-label="Eliminar fotografía">
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center text-body-secondary">No hay imágenes registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
