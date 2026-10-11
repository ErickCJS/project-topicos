<div class="row">
    <div class="col-6 ">
        <div >
            <div class="form-group">
                {!! Form::label('name', 'Nombre') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Nombre de la marca', 'required']) !!}
            </div>
            <div class="form-group mt-2">
                {!! Form::label('description', 'Descripción') !!}
                {!! Form::textarea('description', null, [
                    'class' => 'form-control',
                    'placeholder' => 'Agregue una descripción',
                    'rows' => 3,
                ]) !!}
            </div>
            <div class="form-group mt-2">
                {!! Form::file('logo', [
                    'class' => 'form-control',
                    'id' => 'logo',
                    'accept' => 'image/*'
                ]) !!}
            </div>
        </div>
        
    </div>
    <div class="col-6 d-flex justify-content-center align-items-center ">
        <div class="text-center">
            <p class="mb-2">Vista previa</p>
            <img
                id="preview-logo"
                src="{{ isset($brand) && $brand->logo ? asset($brand->logo) : '#' }}"
                alt="Vista previa"
                style="
                    width: 250px;
                    height: 250px;
                    object-fit: contain;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    padding: 10px;
                    {{ isset($brand) && $brand->logo ? '' : 'display:none;' }}
                "
            >
            <div
                id="preview-placeholder"
                style="{{ isset($brand) && $brand->logo ? 'display:none;' : '' }}"
            >
                <i class="bi bi-image" style="font-size: 80px;"></i>
                <p class="text-muted">No se ha seleccionado una imagen</p>
            </div>

        </div>

    </div>
</div>

<script>
    
    $('#logo').on('change', function (event) {
        const file = event.target.files[0];
        const preview = $('#preview-logo');
        const placeholder = $('#preview-placeholder');

        if (file) {
            preview.attr('src', URL.createObjectURL(file));
            preview.show();
            placeholder.hide();
        } else {
            preview.hide();
            placeholder.show();
        }
    });

</script>
