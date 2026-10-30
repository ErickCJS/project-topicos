<div class="row g-3">
    <div class="col-12">
        <label class="form-label" for="employee-search">Buscar empleado por DNI, nombre o apellido</label>
        <input class="form-control mb-2" type="search" id="employee-search" placeholder="Escriba para filtrar">
        <select class="form-select" name="employee_id" id="employee_id" required>
            <option value="">Seleccione un empleado</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) old('employee_id', $attendance->employee_id ?? '') === (string) $employee->id)>{{ $employee->dni }} - {{ $employee->first_name }} {{ $employee->last_name }}</option>
            @endforeach
        </select>
        <small class="form-text text-muted" id="employee-info"></small>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="shift_id">Turno</label>
        <select class="form-select" name="shift_id" id="shift_id" required>
            <option value="">Seleccione un turno</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}" @selected((string) old('shift_id', $attendance->shift_id ?? '') === (string) $shift->id)>{{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }})</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="attendance-date">Fecha</label>
        <input class="form-control" type="date" name="date" id="attendance-date" value="{{ old('date', isset($attendance) ? $attendance->date->format('Y-m-d') : today()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="attendance-time">Hora</label>
        <input class="form-control" type="time" name="time" id="attendance-time" value="{{ old('time', isset($attendance) ? substr($attendance->time, 0, 5) : now()->format('H:i')) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="attendance-type">Tipo de marcación</label>
        <select class="form-select" name="type" id="attendance-type" required>
            @foreach(\App\Models\admin\Attendance::TYPES as $type)
                <option value="{{ $type }}" @selected(old('type', $attendance->type ?? 'Entrada') === $type)>{{ $type }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="attendance-status">Estado</label>
        <select class="form-select" name="status" id="attendance-status" required>
            @foreach(\App\Models\admin\Attendance::STATUSES as $status)
                <option value="{{ $status }}" @selected(old('status', $attendance->status ?? 'Presente') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label" for="attendance-notes">Notas</label>
        <textarea class="form-control" name="notes" id="attendance-notes" rows="2">{{ old('notes', $attendance->notes ?? '') }}</textarea>
    </div>
    <div class="col-12">
        <strong>Historial de asistencias del día</strong>
        <div class="small text-muted mt-1" id="attendance-day-history">Seleccione un empleado y una fecha.</div>
    </div>
</div>
