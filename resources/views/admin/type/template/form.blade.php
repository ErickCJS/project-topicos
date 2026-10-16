<div class="row g-3">
    <div class="col-12">
        {!! Form::label('name', 'Nombre', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control',
            'placeholder' => 'Nombre del tipo de vehículo',
            'required',
            'maxlength' => 100,
        ]) !!}
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('description', null, [
            'class' => 'form-control',
            'placeholder' => 'Agregue una descripción (opcional)',
            'rows' => 4,
            'maxlength' => 100,
        ]) !!}
    </div>
</div>
