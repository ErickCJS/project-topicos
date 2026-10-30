@php
    $editing = isset($attendance);
@endphp

<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="attendance_employee_id" class="form-label">Empleado *</label>
        <select name="employee_id" id="attendance_employee_id" class="form-select" required>
            <option value="">Seleccione un empleado</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}"
                    @selected((string) old('employee_id', $editing ? $attendance->employee_id : '') === (string) $employee->id)>
                    {{ $employee->dni }} - {{ $employee->first_name }} {{ $employee->last_name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="attendance_shift_id" class="form-label">Turno *</label>
        <select name="shift_id" id="attendance_shift_id" class="form-select" required>
            <option value="">Seleccione un turno</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}"
                    @selected((string) old('shift_id', $editing ? $attendance->shift_id : '') === (string) $shift->id)>
                    {{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="attendance_date" class="form-label">Fecha *</label>
        <input type="date" name="date" id="attendance_date" class="form-control"
            value="{{ old('date', $editing ? $attendance->date->format('Y-m-d') : today()->format('Y-m-d')) }}" required>
    </div>

    <div class="col-12 col-md-6">
        <label for="attendance_time" class="form-label">Hora *</label>
        <input type="time" name="time" id="attendance_time" class="form-control"
            value="{{ old('time', $editing ? substr($attendance->time, 0, 5) : now()->format('H:i')) }}" required>
    </div>

    <div class="col-12 col-md-6">
        <label for="attendance_type" class="form-label">Tipo de marcación *</label>
        <select name="type" id="attendance_type" class="form-select" required>
            <option value="">Seleccione</option>
            @foreach(\App\Models\admin\Attendance::TYPES as $type)
                <option value="{{ $type }}"
                    @selected(old('type', $editing ? $attendance->type : '') === $type)>
                    {{ $type }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="attendance_status" class="form-label">Estado *</label>
        <select name="status" id="attendance_status" class="form-select" required>
            <option value="">Seleccione</option>
            @foreach(\App\Models\admin\Attendance::STATUSES as $status)
                <option value="{{ $status }}"
                    @selected(old('status', $editing ? $attendance->status : '') === $status)>
                    {{ $status }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label for="attendance_notes" class="form-label">Observaciones</label>
        <textarea name="notes" id="attendance_notes" class="form-control" rows="3"
            placeholder="Ingrese alguna observación...">{{ old('notes', $editing ? $attendance->notes : '') }}</textarea>
    </div>
</div>