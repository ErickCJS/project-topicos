
<article class="vehicle-photo-card" data-photo-id="{{ $id }}" style="background-image: url('{{ $url }}');">
    <button type="button" class="vehicle-photo-preview ampliar_foto" data-image="{{ $url }}"
        title="Ampliar fotografía" aria-label="Ampliar fotografía del vehículo">
        <span class="visually-hidden">Ampliar fotografía del vehículo</span>
    </button>

    <div class="vehicle-photo-actions" aria-label="Acciones de la fotografía">
        @unless ($profile)
            <button type="button" class="vehicle-photo-action btn_seleccionar_foto" data-id="{{ $id }}"
                title="Seleccionar como principal" aria-label="Seleccionar como imagen principal">
                <i class="bi bi-star" aria-hidden="true"></i>
            </button>
        @endunless
        <button type="button" class="vehicle-photo-action vehicle-photo-action--danger btn_eliminar_foto" data-id="{{ $id }}"
            title="Eliminar fotografía" aria-label="Eliminar fotografía">
            <i class="bi bi-trash" aria-hidden="true"></i>
        </button>
    </div>

    @if ($profile)
        <span class="vehicle-photo-featured" title="Imagen principal" aria-label="Imagen principal">
            <i class="bi bi-star-fill" aria-hidden="true"></i>
            Principal
        </span>
    @endif
</article>
