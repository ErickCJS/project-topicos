
{{-- Fecha del feriado --}}
<div>
    {!! Form::label('date', 'Fecha del Feriado *', [
        'class' => 'form-label fw-semibold'
    ]) !!}

    {!! Form::date('date', null, [
        'class' => 'form-control',
        'required' => true,
        'min' => now()->toDateString(),
    ]) !!}
</div>

{{-- Nombre del feriado --}}
<div class="mt-3">
    {!! Form::label('name', 'Nombre del Feriado *', [
        'class' => 'form-label fw-semibold'
    ]) !!}

    {!! Form::text('name', null, [
        'class' => 'form-control',
        'required' => true,
    ]) !!}
</div>

{{-- Descripción --}}
<div class="mt-3">
    {!! Form::label('description', 'Descripción', [
        'class' => 'form-label fw-semibold'
    ]) !!}

    {!! Form::textarea('description', null, [
        'class' => 'form-control',
        'placeholder' => 'Descripción del día feriado',
        'rows' => 2,
    ]) !!}
</div>

{{-- Estado --}}
<div class="mt-3">
    {!! Form::label('state', 'Estado', [
        'class' => 'form-label fw-semibold'
    ]) !!}

    {!! Form::select('state', [
        1 => 'Activo',
        0 => 'Inactivo',
    ], null, [
        'class' => 'form-select',
        'required' => true,
    ]) !!}

    <small class="text-secondary">
        Los feriados inactivos no se consideran en las validaciones.
    </small>
</div>
