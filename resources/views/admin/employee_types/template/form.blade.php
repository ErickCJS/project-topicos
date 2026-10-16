<div class="row g-3">
    <div class="col-12">
        {!! Form::label('name', 'Nombre', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control',
            'placeholder' => 'Nombre del tipo de personal',
            'required',
            'maxlength' => 50,
        ]) !!}
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control',
            'placeholder' => 'Descripción del tipo de personal (opcional)',
            'rows' => 4,
            'maxlength' => 255,
        ]) !!}
        <div class="form-text">Máximo 255 caracteres.</div>
    </div>
</div>
