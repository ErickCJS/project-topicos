<div class="attendance-form-intro">
    <h6 class="fw-semibold mb-1"><i class="bi bi-card-checklist me-2" aria-hidden="true"></i>Datos de la asistencia</h6>
    <p class="small mb-0">Seleccione el empleado y registre la información correspondiente a su marcación.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6">
        {!! Form::label('employee_id', 'Empleado', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('employee_id', $employees, null, [
            'class' => 'form-select',
            'placeholder' => 'Seleccione un empleado',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('shift_id', 'Turno', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('shift_id', $shifts, null, [
            'class' => 'form-select',
            'placeholder' => 'Seleccione un turno',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('date', 'Fecha', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::date('date', old('date', isset($attendance) ? $attendance->date?->format('Y-m-d') : null), [
            'class' => 'form-control',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('time', 'Hora', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::input('time', 'time', old('time', isset($attendance) ? substr($attendance->time, 0, 5) : null), [
            'class' => 'form-control',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('type', 'Tipo', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('type', ['Entrada' => 'Entrada', 'Salida' => 'Salida'], null, [
            'class' => 'form-select',
            'placeholder' => 'Seleccione un tipo',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('status', 'Estado', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('status', ['Presente' => 'Presente', 'Ausente' => 'Ausente'], null, [
            'class' => 'form-select',
            'placeholder' => 'Seleccione un estado',
            'required',
        ]) !!}
    </div>

    <div class="col-12">
        {!! Form::label('notes', 'Notas', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::textarea('notes', null, [
            'class' => 'form-control',
            'rows' => 3,
            'placeholder' => 'Información adicional (opcional)',
        ]) !!}
    </div>
</div>
