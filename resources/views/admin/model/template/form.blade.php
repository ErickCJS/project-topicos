<div class="model-form-intro">
    <h6 class="fw-semibold mb-1"><i class="bi bi-box me-2" aria-hidden="true"></i>Datos del modelo</h6>
    <p class="small mb-0">Seleccione la marca e ingrese la información que identifica al modelo.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6">
        {!! Form::label('brand_id', 'Marca', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('brand_id', $brands, null, [
            'class' => 'form-select'.($errors->has('brand_id') ? ' is-invalid' : ''),
            'placeholder' => 'Seleccione una marca',
            'required',
        ]) !!}
        @error('brand_id')
            <div class="invalid-feedback model-field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('name', 'Nombre del modelo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control'.($errors->has('name') ? ' is-invalid' : ''),
            'placeholder' => 'Nombre del modelo',
            'required',
            'maxlength' => 100,
        ]) !!}
        @error('name')
            <div class="invalid-feedback model-field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('code', 'Código', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('code', null, [
            'class' => 'form-control'.($errors->has('code') ? ' is-invalid' : ''),
            'placeholder' => 'Código del modelo (opcional)',
            'maxlength' => 50,
        ]) !!}
        @error('code')
            <div class="invalid-feedback model-field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control'.($errors->has('description') ? ' is-invalid' : ''),
            'placeholder' => 'Descripción del modelo (opcional)',
            'rows' => 4,
        ]) !!}
        @error('description')
            <div class="invalid-feedback model-field-error">{{ $message }}</div>
        @enderror
    </div>
</div>
