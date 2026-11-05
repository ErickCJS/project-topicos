<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\ProgrammingChange;
use App\Models\admin\Reason;
use App\Models\admin\Shift;
use App\Models\admin\Vehicle;
use App\Models\admin\Zone;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ProgrammingChangeController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'action' => ['nullable', 'string', 'max:30'],
        ]);

        if (($filters['start_date'] ?? null) && ($filters['end_date'] ?? null) && $filters['end_date'] < $filters['start_date']) {
            throw ValidationException::withMessages(['end_date' => 'La fecha de fin no puede ser anterior a la fecha de inicio.']);
        }

        $query = ProgrammingChange::query()
            ->with(['programming', 'user', 'reason'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($request->ajax()) {
            return DataTables::eloquent($query)
                ->filter(function ($query) use ($filters) {
                    $query
                        ->when($filters['start_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                        ->when($filters['end_date'] ?? null, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
                        ->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action));
                }, true)
                ->editColumn('created_at', fn (ProgrammingChange $change) => $change->created_at?->format('d/m/Y H:i'))
                ->addColumn('scheduled_date', fn (ProgrammingChange $change) => $change->programming?->date?->format('d/m/Y') ?? 'No disponible')
                ->addColumn('programming_number', fn (ProgrammingChange $change) => $change->programming_id)
                ->addColumn('group', fn (ProgrammingChange $change) => data_get($change->programming?->snapshot, 'group', 'No disponible'))
                ->addColumn('zone', fn (ProgrammingChange $change) => data_get($change->programming?->snapshot, 'zone', ''))
                ->addColumn('reason_name', fn (ProgrammingChange $change) => $change->reason?->name ?? 'No disponible')
                ->addColumn('user_name', fn (ProgrammingChange $change) => $change->user?->name ?? 'Usuario no disponible')
                ->addColumn('actions', fn (ProgrammingChange $change) => '<div class="d-flex justify-content-end gap-2">'
                    .'<button class="btn btn-sm btn-outline-primary" type="button" data-action="detail" data-url="'.e(route('programming-changes.show', $change)).'" aria-label="Ver detalle" title="Ver detalle"><i class="bi bi-eye" aria-hidden="true"></i></button>'
                    .'<button class="btn btn-sm btn-outline-danger" type="button" data-action="delete" data-url="'.e(route('programming-changes.destroy', $change)).'" aria-label="Eliminar registro" title="Eliminar registro"><i class="bi bi-trash" aria-hidden="true"></i></button>'
                    .'</div>')
                ->rawColumns(['actions'])
                ->toJson();
        }

        $actions = ProgrammingChange::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $zones = Zone::orderBy('name')->get(['id', 'name']);
        $shifts = Shift::orderBy('name')->get(['id', 'name', 'start_time', 'end_time']);
        $vehicles = Vehicle::orderBy('name')->get(['id', 'name', 'plate', 'occupats_capacity']);
        $employees = Employee::where('status', 'Activo')->orderBy('last_name')->orderBy('first_name')->get(['id', 'employee_type_id', 'first_name', 'last_name', 'dni']);
        $reasons = Reason::orderBy('name')->get(['id', 'name']);

        return view('admin.programming-changes.index', compact('actions', 'employees', 'filters', 'reasons', 'shifts', 'vehicles', 'zones'));
    }

    public function store(Request $request)
    {
        $type = $request->input('type');
        $valueTable = match ($type) {
            'shift' => 'shifts',
            'driver', 'occupant' => 'employees',
            'vehicle' => 'vehicles',
            default => 'shifts',
        };
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'type' => ['required', Rule::in(['shift', 'driver', 'occupant', 'vehicle'])],
            'value_id' => ['required', 'integer', Rule::exists($valueTable, 'id'), ...($type === 'occupant' ? [Rule::notIn([$request->input('occupant_id')])] : [])],
            'occupant_id' => ['required_if:type,occupant', 'nullable', 'integer', Rule::exists('employees', 'id')->where(fn ($query) => $query->where('employee_type_id', '!=', 1))],
            'reason_id' => ['required', 'integer', 'exists:reason,id'],
            'reason_detail' => ['required', 'string', 'max:1000'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'exists' => 'El valor seleccionado ya no está disponible.',
            'after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'reason_detail.max' => 'El detalle no debe superar los 1000 caracteres.',
        ]);

        if (CarbonImmutable::parse($data['start_date'])->diffInDays(CarbonImmutable::parse($data['end_date'])) > 365) {
            throw ValidationException::withMessages(['end_date' => 'Seleccione un período máximo de 366 días.']);
        }

        $changed = $this->applyMassChange($data, (int) $request->user()->id);

        return response()->json([
            'message' => "El cambio se aplicó a {$changed} programación(es).",
            'changed' => $changed,
        ]);
    }

    public function show(ProgrammingChange $programmingChange)
    {
        $programmingChange->load(['programming', 'user', 'reason']);

        return response()->json([
            'action' => $programmingChange->action,
            'reason' => $programmingChange->reason?->name ?? 'No disponible',
            'reason_detail' => $programmingChange->reason_detail,
            'user' => $programmingChange->user?->name ?? 'Usuario no disponible',
            'changed_at' => $programmingChange->created_at?->format('d/m/Y H:i:s'),
            'programming_id' => $programmingChange->programming_id,
            'programming_date' => $programmingChange->programming?->date?->format('d/m/Y') ?? 'No disponible',
            'before' => $programmingChange->before,
            'after' => $programmingChange->after,
        ]);
    }

    public function destroy(ProgrammingChange $programmingChange)
    {
        $programmingChange->delete();

        return response()->json(['message' => 'Se eliminó el registro del historial. La programación no fue modificada.']);
    }

    private function applyMassChange(array $data, int $userId): int
    {
        return DB::transaction(function () use ($data, $userId) {
            $programs = Programming::query()
                ->with(['employees', 'shift', 'vehicle'])
                ->where('status', 'Programado')
                ->where('zone_id', $data['zone_id'])
                ->whereDate('date', '>=', $data['start_date'])
                ->whereDate('date', '<=', $data['end_date'])
                ->when($data['type'] === 'occupant', fn ($query) => $query->whereHas('employees', fn ($employees) => $employees
                    ->where('employees.id', $data['occupant_id'])->where('programming_employees.role', 'helper')))
                ->orderBy('date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($programs->isEmpty()) {
                $this->reject('No hay programaciones pendientes que coincidan con el rango, la zona y el ocupante seleccionados.', 'start_date');
            }

            $target = $this->massChangeTarget($data);
            if ($target instanceof Employee) {
                $this->validateMassChangeEmployee($target, $programs, $data['type'] === 'driver');
            }
            if ($target instanceof Vehicle) {
                foreach ($programs as $program) {
                    if ($program->employees->count() > (int) $target->occupats_capacity) {
                        $this->reject('El nuevo vehículo no tiene capacidad para todos los ocupantes de las programaciones seleccionadas.', 'value_id');
                    }
                }
            }

            $occupant = $data['type'] === 'occupant' ? Employee::findOrFail($data['occupant_id']) : null;
            if ($occupant && (int) $occupant->employee_type_id === 1) {
                $this->reject('Seleccione un ocupante, no un conductor.', 'occupant_id');
            }
            $this->validateMassChangeConflicts($programs, $data, $target);

            $changed = 0;
            foreach ($programs as $program) {
                $before = $this->massChangeCurrentValue($program, $data['type'], $occupant);
                $after = $this->massChangeTargetValue($target, $data['type']);
                if ($before['id'] === $after['id']) {
                    continue;
                }

                $this->applyMassChangeToProgram($program, $data['type'], $target, $occupant);
                $program->save();
                $program->changes()->create([
                    'user_id' => $userId,
                    'reason_id' => $data['reason_id'],
                    'reason_detail' => $data['reason_detail'],
                    'action' => [
                        'shift' => 'Cambio de turno',
                        'driver' => 'Cambio de conductor',
                        'occupant' => 'Cambio de ocupante',
                        'vehicle' => 'Cambio de vehículo',
                    ][$data['type']],
                    'before' => $before,
                    'after' => $after,
                ]);
                $changed++;
            }

            if ($changed === 0) {
                $this->reject('El valor seleccionado ya está aplicado a todas las programaciones.', 'value_id');
            }

            return $changed;
        }, 3);
    }

    private function massChangeTarget(array $data): Shift|Employee|Vehicle
    {
        return match ($data['type']) {
            'shift' => Shift::findOrFail($data['value_id']),
            'driver', 'occupant' => Employee::with(['contracts', 'vacations'])->findOrFail($data['value_id']),
            'vehicle' => Vehicle::findOrFail($data['value_id']),
        };
    }

    private function validateMassChangeEmployee(Employee $employee, Collection $programs, bool $driver): void
    {
        if ($employee->status !== 'Activo' || (((int) $employee->employee_type_id === 1) !== $driver)) {
            $this->reject($driver ? 'Seleccione un conductor activo.' : 'Seleccione un ocupante activo.', 'value_id');
        }

        foreach ($programs as $program) {
            $date = $program->date->toDateString();
            $hasContract = $employee->contracts->contains(fn ($contract) => $contract->is_active && ! $contract->cancelled_at
                && $contract->start_date->toDateString() <= $date
                && (! $contract->end_date || $contract->end_date->toDateString() >= $date));
            $onVacation = $employee->vacations->contains(fn ($vacation) => $vacation->status === 'Aprobada'
                && $vacation->start_date && $vacation->end_date
                && $vacation->start_date->toDateString() <= $date
                && $vacation->end_date->toDateString() >= $date);
            if (! $hasContract || $onVacation) {
                $this->reject('El personal seleccionado no tiene contrato vigente o tiene vacaciones aprobadas en una de las fechas.', 'value_id');
            }
        }
    }

    private function validateMassChangeConflicts(Collection $programs, array $data, Shift|Employee|Vehicle $target): void
    {
        $ids = $programs->modelKeys();
        $seenEmployees = [];
        $seenVehicles = [];
        $occupantId = $data['type'] === 'occupant' ? (int) $data['occupant_id'] : null;

        foreach ($programs as $program) {
            $shiftId = $data['type'] === 'shift' ? $target->id : $program->shift_id;
            $vehicleId = $data['type'] === 'vehicle' ? $target->id : $program->vehicle_id;
            $employeeIds = $program->employees->pluck('id')->map(fn ($id) => (int) $id)->all();

            if ($data['type'] === 'driver') {
                $employeeIds = array_values(array_diff($employeeIds, [(int) $program->driver_id]));
                $employeeIds[] = (int) $target->id;
            } elseif ($data['type'] === 'occupant') {
                $employeeIds = array_values(array_diff($employeeIds, [$occupantId]));
                $employeeIds[] = (int) $target->id;
            }

            if (count($employeeIds) !== count(array_unique($employeeIds))) {
                $this->reject('Una persona no puede ocupar dos puestos de la misma programación.', 'value_id');
            }

            foreach ($employeeIds as $employeeId) {
                $key = $program->date->toDateString().':'.$shiftId.':'.$employeeId;
                if (isset($seenEmployees[$key]) && $seenEmployees[$key] !== $program->id) {
                    $this->reject('El cambio generaría una asignación duplicada en una misma fecha y turno.', 'value_id');
                }
                $seenEmployees[$key] = $program->id;

                if ($data['type'] === 'shift' || $employeeId === (int) $target->id) {
                    $conflict = DB::table('programming_employees')->where('employee_id', $employeeId)
                        ->whereDate('date', $program->date->toDateString())->where('shift_id', $shiftId)
                        ->whereNotIn('programming_id', $ids)->exists();
                    if ($conflict) {
                        $this->reject('El cambio generaría un conflicto de personal en otra programación.', 'value_id');
                    }
                }
            }

            $vehicleKey = $program->date->toDateString().':'.$shiftId.':'.$vehicleId;
            if (isset($seenVehicles[$vehicleKey]) && $seenVehicles[$vehicleKey] !== $program->id) {
                $this->reject('El cambio generaría una asignación duplicada de vehículo en una misma fecha y turno.', 'value_id');
            }
            $seenVehicles[$vehicleKey] = $program->id;

            if ($data['type'] === 'shift' || $data['type'] === 'vehicle') {
                $conflict = Programming::query()->whereDate('date', $program->date->toDateString())
                    ->where('shift_id', $shiftId)->where('vehicle_id', $vehicleId)
                    ->whereNotIn('id', $ids)->exists();
                if ($conflict) {
                    $this->reject('El cambio generaría un conflicto de vehículo en otra programación.', 'value_id');
                }
            }
        }
    }

    private function massChangeCurrentValue(Programming $program, string $type, ?Employee $occupant): array
    {
        return match ($type) {
            'shift' => ['id' => (int) $program->shift_id, 'label' => $program->snapshot['shift'] ?? $program->shift->name, 'hours' => $program->snapshot['hours'] ?? null],
            'driver' => ['id' => (int) $program->driver_id, 'label' => $program->snapshot['driver'] ?? ''],
            'occupant' => ['id' => (int) $occupant->id, 'label' => trim($occupant->first_name.' '.$occupant->last_name)],
            'vehicle' => ['id' => (int) $program->vehicle_id, 'label' => $program->snapshot['vehicle'] ?? $program->vehicle->name, 'plate' => $program->snapshot['plate'] ?? $program->vehicle->plate],
        };
    }

    private function massChangeTargetValue(Shift|Employee|Vehicle $target, string $type): array
    {
        return match ($type) {
            'shift' => ['id' => (int) $target->id, 'label' => $target->name, 'hours' => substr($target->start_time, 0, 5).' - '.substr($target->end_time, 0, 5)],
            'driver', 'occupant' => ['id' => (int) $target->id, 'label' => trim($target->first_name.' '.$target->last_name)],
            'vehicle' => ['id' => (int) $target->id, 'label' => $target->name, 'plate' => $target->plate],
        };
    }

    private function applyMassChangeToProgram(Programming $program, string $type, Shift|Employee|Vehicle $target, ?Employee $occupant): void
    {
        $snapshot = $program->snapshot ?? [];
        switch ($type) {
            case 'shift':
                $program->shift_id = $target->id;
                $program->vehicle_date_key = $program->date->toDateString().':'.$target->id.':'.$program->vehicle_id;
                DB::table('programming_employees')->where('programming_id', $program->id)->update(['shift_id' => $target->id]);
                $snapshot['shift'] = $target->name;
                $snapshot['hours'] = substr($target->start_time, 0, 5).' - '.substr($target->end_time, 0, 5);
                break;
            case 'driver':
                DB::table('programming_employees')->where('programming_id', $program->id)->where('role', 'driver')->update(['employee_id' => $target->id]);
                $program->driver_id = $target->id;
                $snapshot['driver'] = trim($target->first_name.' '.$target->last_name);
                break;
            case 'occupant':
                DB::table('programming_employees')->where('programming_id', $program->id)->where('employee_id', $occupant->id)->where('role', 'helper')->update(['employee_id' => $target->id]);
                $snapshot['helpers'] = $program->employees->where('pivot.role', 'helper')->map(fn ($employee) => (int) $employee->id === (int) $occupant->id
                    ? trim($target->first_name.' '.$target->last_name)
                    : trim($employee->first_name.' '.$employee->last_name))->values()->all();
                break;
            case 'vehicle':
                $program->vehicle_id = $target->id;
                $program->vehicle_date_key = $program->date->toDateString().':'.$program->shift_id.':'.$target->id;
                $snapshot['vehicle'] = $target->name;
                $snapshot['plate'] = $target->plate;
                break;
        }
        $program->snapshot = $snapshot;
    }

    private function reject(string $message, string $field): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
