<div class="vehicle-form-intro">
    <h6 class="fw-semibold mb-1"><i class="bi bi-truck-front me-2" aria-hidden="true"></i>Datos del vehículo</h6>
    <p class="small mb-0">Complete la identificación, capacidades y clasificación del vehículo.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6">
        {!! Form::label('code', 'Código', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('code', null, [
            'class' => 'form-control'.($errors->has('code') ? ' is-invalid' : ''),
            'placeholder' => 'Ej. VEH-2XIP0',
            'required',
            'maxlength' => 100,
        ]) !!}
        @error('code')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('name', 'Nombre del vehículo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control'.($errors->has('name') ? ' is-invalid' : ''),
            'placeholder' => 'Nombre del vehículo',
            'required',
            'maxlength' => 100,
        ]) !!}
        @error('name')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('plate', 'Placa', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('plate', null, [
            'class' => 'form-control'.($errors->has('plate') ? ' is-invalid' : ''),
            'placeholder' => 'Ej. ABC-123',
            'maxlength' => 10,
            'required',
        ]) !!}
        @error('plate')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('year', 'Año', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('year', null, [
            'class' => 'form-control'.($errors->has('year') ? ' is-invalid' : ''),
            'placeholder' => 'Ej. '.date('Y'),
            'min' => 1900,
            'max' => date('Y') + 1,
            'required',
        ]) !!}
        @error('year')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('type_id', 'Tipo de vehículo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('type_id', $types, null, [
            'class' => 'form-select'.($errors->has('type_id') ? ' is-invalid' : ''),
            'placeholder' => 'Seleccione un tipo',
            'required',
        ]) !!}
        @error('type_id')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        {!! Form::label('color_id', 'Color', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('color_id', $colors, null, [
            'class' => 'form-select'.($errors->has('color_id') ? ' is-invalid' : ''),
            'placeholder' => 'Seleccione un color',
            'required',
        ]) !!}
        @error('color_id')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('brand_id', 'Marca', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('brand_id', $brands, null, [
            'class' => 'form-select'.($errors->has('brand_id') ? ' is-invalid' : ''),
            'id' => 'brand_id',
            'placeholder' => 'Seleccione una marca',
            'required',
        ]) !!}
        @error('brand_id')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('model_id', 'Modelo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('model_id', $models, null, [
            'class' => 'form-select'.($errors->has('model_id') ? ' is-invalid' : ''),
            'id' => 'model_id',
            'placeholder' => 'Seleccione un modelo',
            'required',
        ]) !!}
        @error('model_id')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('load_capacity', 'Carga (Tn)', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('load_capacity', null, [
            'class' => 'form-control'.($errors->has('load_capacity') ? ' is-invalid' : ''),
            'min' => 0,
            'required',
        ]) !!}
        @error('load_capacity')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('compact_capacity', 'Compactación (Tn)', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('compact_capacity', null, [
            'class' => 'form-control'.($errors->has('compact_capacity') ? ' is-invalid' : ''),
            'min' => 0,
            'required',
        ]) !!}
        @error('compact_capacity')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('fuel_capacity', 'Combustible (L)', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('fuel_capacity', null, [
            'class' => 'form-control'.($errors->has('fuel_capacity') ? ' is-invalid' : ''),
            'min' => 0,
            'required',
        ]) !!}
        @error('fuel_capacity')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        {!! Form::label('occupats_capacity', 'Personas', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('occupats_capacity', null, [
            'class' => 'form-control'.($errors->has('occupats_capacity') ? ' is-invalid' : ''),
            'min' => 0,
            'required',
        ]) !!}
        @error('occupats_capacity')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control'.($errors->has('description') ? ' is-invalid' : ''),
            'placeholder' => 'Información adicional del vehículo (opcional)',
            'rows' => 4,
        ]) !!}
        @error('description')<div class="invalid-feedback vehicle-field-error">{{ $message }}</div>@enderror
    </div>
</div>
