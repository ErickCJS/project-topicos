<div class="vehicle-type-form-intro">
    <h6 class="fw-semibold mb-1"><i class="bi bi-truck-front me-2" aria-hidden="true"></i>Datos del tipo de vehículo</h6>
    <p class="small mb-0">Ingrese el nombre y una descripción opcional para identificar este tipo.</p>
</div>

<div class="row g-3">
    <div class="col-12">
        {!! Form::label('name', 'Nombre', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control'.($errors->has('name') ? ' is-invalid' : ''),
            'placeholder' => 'Nombre del tipo de vehículo',
            'required',
            'maxlength' => 100,
        ]) !!}
        @error('name')
            <div class="invalid-feedback vehicle-type-field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control'.($errors->has('description') ? ' is-invalid' : ''),
            'placeholder' => 'Agregue una descripción (opcional)',
            'rows' => 4,
            'maxlength' => 100,
        ]) !!}
        @error('description')
            <div class="invalid-feedback vehicle-type-field-error">{{ $message }}</div>
        @enderror
    </div>
</div>
