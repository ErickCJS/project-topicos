<div class="row g-3">
    <div class="col-12">
        {!! Form::label('name', 'Nombre', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control',
            'placeholder' => 'Nombre del turno',
            'required',
            'maxlength' => 50,
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('start_time', 'Hora de inicio', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::time('start_time', null, ['class' => 'form-control', 'required', 'step' => 60]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('end_time', 'Hora de fin', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::time('end_time', null, ['class' => 'form-control', 'required', 'step' => 60]) !!}
    </div>

    <div class="col-12">
        <div class="form-text">Las horas pueden cruzar la medianoche, por ejemplo, de 22:00 a 06:00.</div>
    </div>
</div>
