<div class="row g-3">
    <div class="col-12">
        {!! Form::label('name', 'Nombre del turno *', ['class' => 'form-label']) !!}
        {!! Form::text('name', null, [
            'class' => 'form-control',
            'placeholder' => 'Nombre del turno',
            'required',
            'maxlength' => 50,
        ]) !!}
        <div class="form-text">Ingrese un nombre que permita identificar el horario.</div>
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('start_time', 'Hora de inicio *', ['class' => 'form-label']) !!}
        {!! Form::time('start_time', isset($shift) ? substr((string) $shift->start_time, 0, 5) : null, ['class' => 'form-control', 'required', 'step' => 60]) !!}
        <div class="form-text">Formato de 24 horas.</div>
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('end_time', 'Hora de término *', ['class' => 'form-label']) !!}
        {!! Form::time('end_time', isset($shift) ? substr((string) $shift->end_time, 0, 5) : null, ['class' => 'form-control', 'required', 'step' => 60]) !!}
        <div class="form-text">Formato de 24 horas.</div>
    </div>

    <div class="col-12">
        {!! Form::label('description', 'Descripción', ['class' => 'form-label']) !!}
        {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'maxlength' => 1000, 'placeholder' => 'Descripción del turno (opcional)']) !!}
    </div>
    <div class="col-12">
        <div class="shift-form-note"><i class="bi bi-info-circle me-2" aria-hidden="true"></i>Configure las horas de entrada y salida. Si el término es anterior al inicio, el turno finaliza al día siguiente.</div>
    </div>
</div>
