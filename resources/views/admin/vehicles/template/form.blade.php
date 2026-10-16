<div class="row g-3">
    <div class="col-12 col-md-6">
        {!! Form::label('code', 'Código', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('code', null, [
            'class' => 'form-control',
            'placeholder' => 'Ej. VEH-2XIP0',
            'required',
            'maxlength' => 100,
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('name', 'Nombre del vehículo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control',
            'placeholder' => 'Nombre del vehículo',
            'required',
            'maxlength' => 100,
        ]) !!}
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('plate', 'Placa', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('plate', null, [
            'class' => 'form-control',
            'placeholder' => 'Ej. ABC-123',
            'maxlength' => 10,
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('year', 'Año', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('year', null, [
            'class' => 'form-control',
            'placeholder' => 'Ej. '.date('Y'),
            'min' => 1900,
            'max' => date('Y') + 1,
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('type_id', 'Tipo de vehículo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('type_id', $types, null, [
            'class' => 'form-select',
            'placeholder' => 'Seleccione un tipo',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('color_id', 'Color', ['class' => 'form-label fw-semibold']) !!}
        <select class="form-select" id="color_id" name="color_id" required>
            <option value="">Seleccione un color</option>
            @foreach($colors as $color)
                <option value="{{ $color->id }}" data-color="{{ $color->code }}"
                    @selected(old('color_id', isset($vehicle) ? $vehicle->color_id : '') == $color->id)>
                    {{ $color->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('brand_id', 'Marca', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('brand_id', $brands, old('brand_id', isset($vehicle) ? $vehicle->brand_id : $brands->keys()->first()), [
            'class' => 'form-select',
            'id' => 'brand_id',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('model_id', 'Modelo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('model_id', $models, old('model_id', isset($vehicle) ? $vehicle->model_id : $models->keys()->first()), [
            'class' => 'form-select',
            'id' => 'model_id',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('load_capacity', 'Carga (Tn)', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('load_capacity', null, [
            'class' => 'form-control',
            'min' => 0,
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('compact_capacity', 'Compactación (Tn)', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('compact_capacity', null, [
            'class' => 'form-control',
            'min' => 0,
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('fuel_capacity', 'Combustible (L)', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('fuel_capacity', null, [
            'class' => 'form-control',
            'min' => 0,
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('occupats_capacity', 'Personas', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('occupats_capacity', null, [
            'class' => 'form-control',
            'min' => 0,
            'required',
        ]) !!}
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control',
            'placeholder' => 'Información adicional del vehículo (opcional)',
            'rows' => 4,
        ]) !!}
    </div>
</div>
