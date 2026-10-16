<div class="contract-form-intro">
    <h6 class="fw-semibold mb-1">
        <i class="bi bi-file-earmark-text me-2" aria-hidden="true"></i>Datos del contrato
    </h6>
    <p class="small mb-0">Los campos marcados con asterisco son obligatorios.</p>
</div>

<div class="row g-3">
    <div class="col-12 col-md-6">
        {!! Form::label('employee_id', 'Trabajador *', ['class' => 'form-label fw-semibold']) !!}
        @isset($contract)
            {!! Form::hidden('employee_id', $contract->employee_id) !!}
            {!! Form::select('employee_display', $employees, $contract->employee_id, [
                'class' => 'form-select contract-select',
                'id' => 'employee_id',
                'disabled',
            ]) !!}
            <div class="form-text">El trabajador no puede cambiarse después del registro.</div>
        @else
            {!! Form::select('employee_id', $employees, null, [
                'class' => 'form-select contract-select',
                'placeholder' => 'Seleccione un trabajador',
                'required',
            ]) !!}
        @endisset
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('contract_type', 'Tipo de contrato *', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::select('type', $types, null, [
            'class' => 'form-select',
            'id' => 'contract_type',
            'placeholder' => 'Seleccione un tipo',
            'required',
        ]) !!}
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('salary', 'Sueldo (S/) *', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::number('salary', null, [
            'class' => 'form-control',
            'min' => '1350',
            'max' => '999999.99',
            'step' => '0.01',
            'placeholder' => '1350.00',
            'required',
        ]) !!}
        <div class="form-text">Sueldo mínimo: S/ 1,350.00.</div>
    </div>

    <div class="col-12 col-md-6">
        {!! Form::label('start_date', 'Fecha de inicio *', ['class' => 'form-label fw-semibold']) !!}
        @php
            $started = isset($contract) && $contract->start_date->lt(today());
            $startAttributes = [
                'class' => 'form-control',
                'min' => today()->toDateString(),
                'required',
            ];
            if ($started) {
                $startAttributes['readonly'] = true;
                $startAttributes['min'] = $contract->start_date->toDateString();
            }
        @endphp
        {!! Form::date(
            'start_date',
            isset($contract) ? $contract->start_date->format('Y-m-d') : today()->toDateString(),
            $startAttributes,
        ) !!}
    </div>

    <div class="col-12 col-md-6" id="contract_end_group">
        {!! Form::label('contract_end_date', 'Fecha de término *', ['class' => 'form-label fw-semibold']) !!}
        {!! Form::date(
            'end_date',
            isset($contract) ? $contract->end_date?->format('Y-m-d') : null,
            ['class' => 'form-control', 'id' => 'contract_end_date'],
        ) !!}
        <div class="form-text">Duración máxima: 3 meses. Puede registrar un periodo menor.</div>
    </div>

    <div class="col-12 col-md-6" id="contract_next_hiring_group">
        <label class="form-label fw-semibold" for="contract_next_hiring">Próxima fecha de contratación</label>
        <input type="date" id="contract_next_hiring" class="form-control" readonly>
        <div class="form-text">Se calcula sumando 2 meses a la fecha de término.</div>
    </div>
</div>

@if($employees->isEmpty())
    <div class="alert alert-info mt-3 mb-0" role="alert">
        Primero debe registrar trabajadores en el módulo de personal.
    </div>
@endif
