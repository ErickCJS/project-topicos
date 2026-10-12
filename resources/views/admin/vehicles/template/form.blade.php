<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('code', 'Código *') !!}
            {!! Form::text('code', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el código (Ej: VEH-2XIP0)', 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('name', 'Nombre del vehículo *') !!}
            {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el nombre (Ej: VEHICUL001)', 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('year', 'Año *') !!}
            {!! Form::number('year', null, ['class' => 'form-control', 'placeholder' => 'Ingrese el año (Ej: 2025)', 'min' => 1900, 'max' => date('Y') + 1, 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('brand_id', 'Marca *') !!}
            {!! Form::select('brand_id', $brands, null, ['class' => 'form-control', 'id' => 'brand_id', 'placeholder' => 'Seleccione una marca', 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('load_capacity', 'Capacidad de Carga (Tn) *') !!}
            {!! Form::number('load_capacity', null, ['class' => 'form-control', 'placeholder' => 'Ingrese la capacidad de carga (Ej: 9528)', 'min' => 0, 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('compact_capacity', 'Capacidad de Compactación (Tn) *') !!}
            {!! Form::number('compact_capacity', null, ['class' => 'form-control', 'placeholder' => 'Ingrese la capacidad de compactación (Ej: 180)', 'min' => 0, 'required']) !!}
        </div>

   
    </div>

    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('type_id', 'Tipo de Vehículo *') !!}
            {!! Form::select('type_id', $types, null, ['class' => 'form-control', 'placeholder' => 'Seleccione un tipo', 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('plate', 'Placa *') !!}
            {!! Form::text('plate', null, ['class' => 'form-control', 'placeholder' => 'Ingrese la placa (Ej: ABC-123)', 'maxlength' => 10, 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('color_id', 'Color *') !!}
            {!! Form::select('color_id', $colors, null, ['class' => 'form-control', 'placeholder' => 'Seleccione un color', 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('model_id', 'Modelo *') !!}
            {!! Form::select('model_id', $models, null, ['class' => 'form-control', 'id' => 'model_id', 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('fuel_capacity', 'Capacidad de Combustible (L) *') !!}
            {!! Form::number('fuel_capacity', null, ['class' => 'form-control', 'placeholder' => 'Ingrese la capacidad de combustible (Ej: 60)', 'min' => 0, 'required']) !!}
        </div>

        <div class="form-group mt-2">
            {!! Form::label('occupats_capacity', 'Capacidad de Personas *') !!}
            {!! Form::number('occupats_capacity', null, ['class' => 'form-control', 'placeholder' => 'Ingrese la capacidad de personas (Ej: 3)', 'min' => 0, 'required']) !!}
        </div>
    </div>
    <div class="form-group mt-2 w-10">
        {!! Form::label('description', 'Descripción') !!}
        {!! Form::textarea('description', null, ['class' => 'form-control', 'placeholder' => 'Ingrese la descripción', 'rows' => 3]) !!}
    </div>
</div>

<script>
$(document).on('change', '#brand_id', function () {
    const id_brand = $(this).val();
    const model = $('#model_id');
        model.empty();
    if (!id_brand) return;

    $.ajax({
        url: "{{ route('vehicles.retonar_models', ':id_brand') }}"
            .replace(':id_brand', id_brand),
        type: 'GET',
        dataType: 'json',

        success: function (response) {
            $.each(response, function (id, nombre) {
                model.append(
                    $('<option>', {
                        value: id,
                        text: nombre
                    })
                );
            });
        },

        error: function (xhr) {
            console.error('Error al cargar categorías:', xhr.responseText);
        }
    });
    
});

</script>
