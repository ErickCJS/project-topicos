<div class="row g-3">
    <div class="col-12">
        {!! Form::label('name', 'Nombre', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control'.($errors->has('name') ? ' is-invalid' : ''),
            'placeholder' => 'Nombre del tipo de personal',
            'required',
            'maxlength' => 50,
        ]) !!}
        @error('name')
            <div class="invalid-feedback employee-type-field-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control'.($errors->has('description') ? ' is-invalid' : ''),
            'placeholder' => 'Descripción del tipo de personal (opcional)',
            'rows' => 4,
            'maxlength' => 255,
        ]) !!}
        @error('description')
            <div class="invalid-feedback employee-type-field-error">{{ $message }}</div>
        @enderror
        <div class="form-text">Máximo 255 caracteres.</div>
    </div>
</div>
