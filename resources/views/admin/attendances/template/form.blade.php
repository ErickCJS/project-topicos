@php
    $editing = isset($attendance);
    $selectedDate = old('date', $editing ? $attendance->date->format('Y-m-d') : ($defaultDate ?? today()->format('Y-m-d')));
    $selectedTime = old('time', $editing ? substr($attendance->time, 0, 5) : ($defaultTime ?? now()->format('H:i')));
    $selectedEmployeeId = old('employee_id', $editing ? $attendance->employee_id : '');
    $selectedShift = old('shift_id', $editing ? $attendance->shift_id : ($defaultShiftId ?? ''));
    $selectedType = old('type', $editing ? $attendance->type : ($defaultType ?? 'Entrada'));
@endphp

<div class="attendance-form-fields" data-attendance-mode="{{ $editing ? 'edit' : 'create' }}">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <label for="attendance_employee_id" class="form-label">Trabajador *</label>
            <select name="employee_id" id="attendance_employee_id" class="form-select" required>
                <option value="">Seleccione un trabajador</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}"
                        @selected((string) $selectedEmployeeId === (string) $employee->id)>
                        {{ $employee->dni }} - {{ $employee->first_name }} {{ $employee->last_name }}
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Puede registrar asistencia aunque no tenga programación asignada.</small>
        </div>

        <div class="col-12 col-md-6">
            <label for="attendance_shift_id" class="form-label">Turno *</label>
            <select name="shift_id" id="attendance_shift_id" class="form-select" required>
                <option value="">Seleccione un turno</option>
                @foreach($shifts as $shift)
                    <option value="{{ $shift->id }}"
                        @selected((string) $selectedShift === (string) $shift->id)>
                        {{ $shift->name }} ({{ substr($shift->start_time, 0, 5) }} - {{ substr($shift->end_time, 0, 5) }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted">El turno se sugiere según la fecha y hora de la marcación.</small>
        </div>

        <div class="col-12 col-md-6">
            <label for="attendance_date" class="form-label">Fecha de marcación *</label>
            <input type="date" name="date" id="attendance_date" class="form-control"
                value="{{ $selectedDate }}" required>
        </div>

        <div class="col-12 col-md-6">
            <label for="attendance_time" class="form-label">Hora de marcación *</label>
            <input type="time" name="time" id="attendance_time" class="form-control"
                value="{{ $selectedTime }}" required>
        </div>

        <div class="col-12">
            <label for="attendance_type" class="form-label">Tipo de marcación *</label>
            <select name="type" id="attendance_type" class="form-select" required>
                @foreach(\App\Models\admin\Attendance::TYPES as $type)
                    <option value="{{ $type }}" @selected($selectedType === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <small class="text-muted">Se sugiere Salida cuando el trabajador tiene una Entrada pendiente.</small>
        </div>

        <div class="col-12">
            <div class="alert alert-info mb-0 py-2" role="status" aria-live="polite">
                <div class="fw-semibold mb-1">Asignación automática</div>
                <div class="small" id="attendance-suggestion-feedback">
                    @if($editing)
                        Estás editando una marcación individual. Se conservaron su trabajador, turno, fecha, hora y tipo.
                    @else
                        La fecha y hora se completan con el momento actual. Al seleccionar al trabajador se sugerirán el turno y el tipo de marcación.
                    @endif
                </div>
                @if($editing)
                    <div class="small mt-2">
                        Estado actual:
                        <span class="badge {{ $attendance->status === 'Presente' ? 'bg-success' : ($attendance->status === 'Tardanza' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ $attendance->status }}
                        </span>
                        · Origen: <strong>{{ $attendance->origin ?? 'No programado' }}</strong>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-12">
            <label for="attendance_notes" class="form-label">Observaciones</label>
            <textarea name="notes" id="attendance_notes" class="form-control" rows="3"
                placeholder="Ingrese alguna observación...">{{ old('notes', $editing ? $attendance->notes : '') }}</textarea>
        </div>

        <div class="col-12">
            <div class="border-top pt-3 mt-1">
                <h6 class="mb-2">Historial del día</h6>
                <div id="attendance-day-history" class="small text-muted" aria-live="polite">
                    Seleccione un trabajador y una fecha para consultar sus marcaciones.
                </div>
            </div>
        </div>
    </div>
</div>
