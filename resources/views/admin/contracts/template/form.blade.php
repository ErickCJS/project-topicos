<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('employee_id', 'Trabajador *') !!}
            @isset($contract)
                {!! Form::hidden('employee_id', $contract->employee_id) !!}
                {!! Form::select('employee_display', $employees, $contract->employee_id, ['class' => 'form-control', 'id' => 'employee_id', 'disabled']) !!}
            @else
                {!! Form::select('employee_id', $employees, null, ['class' => 'form-control', 'placeholder' => 'Seleccione un trabajador', 'required']) !!}
            @endisset
        </div>
        <div class="form-group mt-2">
            {!! Form::label('contract_type', 'Tipo de contrato *') !!}
            {!! Form::select('type', $types, null, ['class' => 'form-control', 'id' => 'contract_type', 'placeholder' => 'Seleccione un tipo', 'required']) !!}
        </div>
        <div class="form-group mt-2">
            {!! Form::label('salary', 'Sueldo (S/) *') !!}
            {!! Form::number('salary', null, ['class' => 'form-control', 'min' => 0, 'max' => '999999.99', 'step' => '0.01', 'required']) !!}
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            {!! Form::label('start_date', 'Fecha de inicio *') !!}
            {!! Form::date('start_date', isset($contract) ? $contract->start_date->format('Y-m-d') : null, ['class' => 'form-control', 'required']) !!}
        </div>
        <div class="form-group mt-2" id="contract_end_group">
            {!! Form::label('contract_end_date', 'Fecha de término *') !!}
            {!! Form::date('end_date', isset($contract) ? $contract->end_date?->format('Y-m-d') : null, ['class' => 'form-control', 'id' => 'contract_end_date']) !!}
            <small class="form-text text-muted">Duración máxima: 3 meses. Puede registrar un periodo menor.</small>
        </div>
        <div class="form-group mt-2" id="contract_next_hiring_group">
            <label for="contract_next_hiring">Próxima fecha de contratación</label>
            <input type="date" id="contract_next_hiring" class="form-control" readonly>
            <small class="form-text text-muted">Se calcula sumando 2 meses a la fecha de término.</small>
        </div>
    </div>
</div>
@if($employees->isEmpty())
    <div class="alert alert-info mt-3">Primero debe registrar trabajadores en el módulo de personal.</div>
@endif
