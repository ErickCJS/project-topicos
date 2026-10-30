@php
    $editing = isset($vacation);
    $historical = $editing && $vacation->isHistorical();
    $selectedEmployee = $editing ? $vacation->employee_id : null;
    $selectedYear = $editing ? $vacation->year : today()->year;
@endphp

<div class="alert alert-danger vacation-general-error d-none" role="alert"></div>

@if($historical)
    <div class="alert alert-warning" role="alert">
        <strong>Pendiente de regularización.</strong>
        Asigne un rango de exactamente {{ $vacation->scheduled_days }} días calendario. La reserva existente no se duplicará.
    </div>
@endif

<div class="row g-3">
    <div class="col-12 col-md-8">
        <label for="vacation_employee_id" class="form-label">Trabajador *</label>
        @if($editing)
            <input type="hidden" name="employee_id" value="{{ $vacation->employee_id }}">
            <input type="text" class="form-control" value="{{ $vacation->employee->dni }} - {{ $vacation->employee->first_name }} {{ $vacation->employee->last_name }}" disabled>
        @else
            <select name="employee_id" id="vacation_employee_id" class="form-select" required>
                <option value="">Seleccione un trabajador</option>
                @foreach($employees as $employee)
                    @php $contract = $employee->contracts->first(); @endphp
                    <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>
                        {{ $employee->dni }} - {{ $employee->first_name }} {{ $employee->last_name }}{{ $contract ? ' ('.$contract->type.')' : '' }}
                    </option>
                @endforeach
            </select>
        @endif
        <div class="invalid-feedback d-none" data-error-for="employee_id"></div>
    </div>

    <div class="col-12 col-md-4">
        <label for="vacation_year" class="form-label">Año *</label>
        <input type="number" name="year" id="vacation_year" class="form-control" min="1900" max="2100"
            value="{{ old('year', $selectedYear) }}" {{ $historical ? 'readonly' : '' }} required>
        <div class="invalid-feedback d-none" data-error-for="year"></div>
    </div>

    <div class="col-12 col-md-6">
        <label for="vacation_start_date" class="form-label">Fecha de inicio *</label>
        <input type="date" name="start_date" id="vacation_start_date" class="form-control" data-historical="{{ $historical ? '1' : '0' }}" data-original-start="{{ $editing ? $vacation->start_date?->toDateString() : '' }}"
            value="{{ old('start_date', $editing ? $vacation->start_date?->toDateString() : '') }}" required>
        <div class="invalid-feedback d-none" data-error-for="start_date"></div>
    </div>

    <div class="col-12 col-md-6">
        <label for="vacation_end_date" class="form-label">Fecha de término *</label>
        <input type="date" name="end_date" id="vacation_end_date" class="form-control"
            value="{{ old('end_date', $editing ? $vacation->end_date?->toDateString() : '') }}" required>
        <div class="invalid-feedback d-none" data-error-for="end_date"></div>
    </div>

    <div class="col-12 col-md-4">
        <div class="vacation-balance-card h-100">
            <span>Días disponibles</span>
            <strong><span data-vacation-available>—</span> días</strong>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="vacation-balance-card h-100">
            <span>Días solicitados</span>
            <strong><span data-vacation-days>—</span> días</strong>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="vacation-balance-card h-100">
            <span data-vacation-result-label>Saldo restante</span>
            <strong><span data-vacation-balance>—</span> días</strong>
        </div>
    </div>

    <div class="col-12">
        <div class="alert alert-danger d-none mb-0" data-vacation-excess role="alert"></div>
    </div>
</div>

@if(! $editing && $employees->isEmpty())
    <div class="alert alert-info mt-3 mb-0" role="alert">
        No hay empleados activos con contrato vigente Permanente o Nombrado.
    </div>
@endif
