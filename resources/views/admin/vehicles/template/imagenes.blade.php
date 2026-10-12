{{-- IMÁGENES --}}
<div class="col-md-12">
    <div class="card">
        <div class="card-body">

            {!! Form::label('photos', 'Seleccionar imágenes') !!}
            <div class="">
                <input type="file"
                    id="photos"
                    accept="image/*"
                    multiple
                    class="form-control" required>
                
                <button type="button" class="btn_subir btn btn-sm btn-secondary mt-2">Subir imagenes</button>
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
                        <img src="{{ asset('storage/' . $photo->imagen) }}" alt="Foto del vehículo" class="img-thumbnail ampliar_foto"
                            width="80"
                            height="80"
                            style="object-fit: cover; cursor: zoom-in;">
                    </td>
                    <td class="text-center">
                         <input type="radio"
                            name="foto_principal"
                            class="form-check-input radio_principal"
                            value="{{ $photo->id }}"
                            {{ $photo->profile ? 'checked' : '' }}>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm btn_eliminar_foto" data-id="{{ $photo->id }}">
                           <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">
                        No hay imágenes registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
   
</div>

<script>


    window.vehicle_id = @json($vehicle_id);


</script>