<?php

namespace App\Services;

use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\Shift;
use App\Models\admin\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkProgrammingModificationService
{
    public function __construct(private ProgrammingModificationService $modifications) {}

    public function preview(array $data): array
    {
        $this->validateDates($data);
        $rows = $this->queryRows($data)->with(['shift', 'employees', 'vehicle'])->get();
        if ($rows->isEmpty()) {
            return ['rows' => [], 'ids' => [], 'valid' => false, 'message' => 'No hay programaciones programadas en la zona y rango seleccionados.'];
        }

        $states = [];
        $reports = [];
        foreach ($rows as $row) {
            try {
                $state = $this->stateFor($row, $data);
                $this->validateRow($row, $state, $rows->modelKeys());
                $states[$row->id] = $state;
                $reports[] = $this->report($row, $state, []);
            } catch (ValidationException $exception) {
                $reports[] = $this->report($row, null, $this->messages($exception));
            }
        }

        foreach ($this->internalConflicts($rows, $states) as $id => $messages) {
            $index = collect($reports)->search(fn ($report) => (int) $report['id'] === (int) $id);
            if ($index !== false) {
                $reports[$index]['errors'] = array_values(array_unique(array_merge($reports[$index]['errors'], $messages)));
                $reports[$index]['valid'] = false;
            }
        }

        $valid = $reports !== [] && collect($reports)->every(fn ($report) => $report['valid']);
        return ['rows' => $reports, 'ids' => $rows->modelKeys(), 'valid' => $valid, 'message' => $valid ? null : 'Corrija los conflictos antes de confirmar el cambio masivo.'];
    }

    public function apply(array $data, int $userId, string $operationId): int
    {
        $this->validateDates($data);
        try {
            return DB::transaction(function () use ($data, $userId, $operationId): int {
                $this->modifications->validateReason($data);
                $ids = array_values(array_unique(array_map('intval', $data['ids'] ?? [])));
                $rows = $this->queryRows($data)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
                if (! $ids || $rows->count() !== count($ids)) {
                    $this->reject('Las programaciones seleccionadas cambiaron o dejaron de estar disponibles. Consulte nuevamente.');
                }

                $rows->load(['shift', 'employees', 'vehicle']);
                $states = [];
                $shiftIds = $rows->pluck('shift_id')->push($this->newShiftId($data))->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();
                $vehicleIds = $rows->pluck('vehicle_id')->push($this->newVehicleId($data))->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();
                $employeeIds = $rows->flatMap(fn (Programming $row) => $row->employees->pluck('id'))->push($this->newEmployeeId($data))->filter()->map(fn ($id) => (int) $id)->unique()->sort()->values();

                $shifts = Shift::whereIn('id', $shiftIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $vehicles = Vehicle::whereIn('id', $vehicleIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $employees = Employee::whereIn('id', $employeeIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($rows as $row) {
                    $state = $this->stateFor($row, $data, $shifts, $vehicles, $employees);
                    $this->validateRow($row, $state, $rows->modelKeys(), $shifts, $vehicles, $employees);
                    $states[$row->id] = $state;
                }
                $this->rejectInternalConflicts($rows, $states);

                foreach ($rows as $row) {
                    $state = $states[$row->id];
                    $before = $this->historyValue($row, $data);
                    $snapshot = $row->snapshot ?? [];
                    $snapshot['shift'] = $state['shift']->name;
                    $snapshot['hours'] = substr($state['shift']->start_time, 0, 5).' - '.substr($state['shift']->end_time, 0, 5);
                    $snapshot['vehicle'] = $state['vehicle']->name;
                    $snapshot['plate'] = $state['vehicle']->plate;
                    $snapshot['driver'] = $this->employeeName($employees[$state['driver_id']]);
                    $snapshot['helpers'] = collect($state['helper_ids'])->map(fn ($id) => $this->employeeName($employees[$id]))->values()->all();

                    $row->update([
                        'shift_id' => $state['shift']->id,
                        'vehicle_id' => $state['vehicle']->id,
                        'driver_id' => $state['driver_id'],
                        'snapshot' => $snapshot,
                        'vehicle_date_key' => $row->date->toDateString().':'.$state['shift']->id.':'.$state['vehicle']->id,
                    ]);
                    $row->employees()->detach();
                    $assignments = [];
                    foreach (array_merge([$state['driver_id']], $state['helper_ids']) as $employeeId) {
                        $assignments[$employeeId] = ['date' => $row->date->toDateString(), 'shift_id' => $state['shift']->id, 'role' => (int) $employeeId === (int) $state['driver_id'] ? 'driver' : 'helper'];
                    }
                    $row->employees()->attach($assignments);

                    $row->changes()->create([
                        'user_id' => $userId,
                        'operation_id' => $operationId,
                        'action' => 'Cambio masivo de '.$this->typeLabel($data['type']),
                        'reason' => $data['reason'],
                        'description' => $data['description'] ?? null,
                        'before' => $before,
                        'after' => [...$this->historyAfter($row, $data), 'type' => $data['type'], 'bulk_operation_id' => $operationId],
                    ]);
                }

                return $rows->count();
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                $this->reject('No se pudo aplicar el cambio masivo porque un recurso fue reservado en otro recorrido. Consulte nuevamente.');
            }
            throw $exception;
        }
    }

    private function queryRows(array $data)
    {
        return Programming::query()
            ->where('status', 'Programado')
            ->where('zone_id', $data['zone_id'])
            ->whereDate('date', '>=', $data['start_date'])
            ->whereDate('date', '<=', $data['end_date'])
            ->when($data['type'] === 'helper', fn ($query) => $query->whereHas('employees', fn ($employees) => $employees->where('employees.id', $data['old_employee_id'])->where('programming_employees.role', 'helper')))
            ->orderBy('date')->orderBy('id');
    }

    private function validateDates(array $data): void
    {
        if (empty($data['start_date']) || empty($data['end_date'])) {
            $this->reject('La fecha inicial y la fecha final son obligatorias.');
        }
        if (($data['end_date'] ?? '') < ($data['start_date'] ?? '')) {
            $this->reject('La fecha final no puede ser anterior a la fecha inicial.');
        }
    }

    private function stateFor(Programming $row, array $data, ?Collection $shifts = null, ?Collection $vehicles = null, ?Collection $employees = null): array
    {
        $currentHelpers = $row->employees->filter(fn ($employee) => $employee->pivot->role === 'helper')->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $helperIds = $currentHelpers;
        $driverId = (int) $row->driver_id;
        $shiftId = (int) $row->shift_id;
        $vehicleId = (int) $row->vehicle_id;

        if ($data['type'] === 'shift') {
            $shiftId = (int) $data['new_shift_id'];
        } elseif ($data['type'] === 'driver') {
            $driverId = (int) $data['new_driver_id'];
        } elseif ($data['type'] === 'vehicle') {
            $vehicleId = (int) $data['new_vehicle_id'];
        } elseif ($data['type'] === 'helper') {
            $oldId = (int) $data['old_employee_id'];
            $index = array_search($oldId, $helperIds, true);
            if ($index === false) {
                $this->reject('El ocupante seleccionado ya no pertenece a todas las programaciones afectadas. Consulte nuevamente.');
            }
            $helperIds[$index] = (int) $data['new_employee_id'];
        }

        return [
            'shift_id' => $shiftId,
            'vehicle_id' => $vehicleId,
            'driver_id' => $driverId,
            'helper_ids' => $helperIds,
            'shift' => ($shifts ?? collect())->get($shiftId) ?? Shift::findOrFail($shiftId),
            'vehicle' => ($vehicles ?? collect())->get($vehicleId) ?? Vehicle::findOrFail($vehicleId),
            'employees' => $employees,
        ];
    }

    private function validateRow(Programming $row, array $state, array $excludedIds, ?Collection $shifts = null, ?Collection $vehicles = null, ?Collection $employees = null): void
    {
        if ((int) $state['shift_id'] === (int) $row->shift_id && (int) $state['vehicle_id'] === (int) $row->vehicle_id && (int) $state['driver_id'] === (int) $row->driver_id && $state['helper_ids'] === $row->employees->filter(fn ($employee) => $employee->pivot->role === 'helper')->pluck('id')->map(fn ($id) => (int) $id)->values()->all()) {
            $this->reject('La programación ya tiene el valor seleccionado para el cambio.');
        }

        $employees ??= Employee::whereIn('id', array_merge([$state['driver_id']], $state['helper_ids']))->get()->keyBy('id');
        $this->modifications->validateAvailability($row, $state['shift'], $state['vehicle'], $employees, $state['driver_id'], $state['helper_ids'], $this->externalRoutes($row, $state['shift'], $excludedIds));

        $oldCapacity = (int) ($vehicles?->get($row->vehicle_id)?->occupats_capacity ?? $row->vehicle->occupats_capacity);
        if ((int) $state['vehicle']->occupats_capacity !== $oldCapacity) {
            $this->reject('El vehículo nuevo debe conservar la misma capacidad de ocupantes que el actual.');
        }
    }

    private function externalRoutes(Programming $row, Shift $shift, array $excludedIds): Collection
    {
        return $this->modifications->overlappingRoutes($row, $shift, $excludedIds);
    }

    private function internalConflicts(Collection $rows, array $states): array
    {
        $errors = [];
        foreach ($rows as $index => $left) {
            if (! isset($states[$left->id])) continue;
            foreach ($rows->slice($index + 1) as $right) {
                if (! isset($states[$right->id])) continue;
                [$leftStart, $leftEnd] = $this->modifications->interval(CarbonImmutable::parse($left->date->toDateString()), $states[$left->id]['shift']);
                [$rightStart, $rightEnd] = $this->modifications->interval(CarbonImmutable::parse($right->date->toDateString()), $states[$right->id]['shift']);
                if ($leftStart >= $rightEnd || $rightStart >= $leftEnd) continue;
                $sameVehicle = (int) $states[$left->id]['vehicle_id'] === (int) $states[$right->id]['vehicle_id'];
                $leftPeople = array_merge([$states[$left->id]['driver_id']], $states[$left->id]['helper_ids']);
                $rightPeople = array_merge([$states[$right->id]['driver_id']], $states[$right->id]['helper_ids']);
                $samePerson = array_intersect($leftPeople, $rightPeople) !== [];
                if ($sameVehicle || $samePerson) {
                    $message = $sameVehicle ? 'El vehículo nuevo queda asignado a otro recorrido con horario superpuesto.' : 'El personal nuevo queda asignado a otro recorrido con horario superpuesto.';
                    $errors[$left->id][] = $message;
                    $errors[$right->id][] = $message;
                }
            }
        }
        return $errors;
    }

    private function rejectInternalConflicts(Collection $rows, array $states): void
    {
        if ($errors = $this->internalConflicts($rows, $states)) {
            $this->reject(collect($errors)->flatten()->first());
        }
    }

    private function report(Programming $row, ?array $state, array $errors): array
    {
        return ['id' => $row->id, 'date' => $row->date->toDateString(), 'zone' => $row->snapshot['zone'] ?? null, 'current' => $this->currentValue($row), 'new' => $state ? $this->newValue($state) : null, 'errors' => array_values(array_unique($errors)), 'valid' => $errors === [] && $state !== null];
    }

    private function messages(ValidationException $exception): array
    {
        return collect($exception->errors())->flatten()->values()->all();
    }

    private function currentValue(Programming $row): array
    {
        return ['shift' => $row->snapshot['shift'] ?? null, 'vehicle' => trim(($row->snapshot['vehicle'] ?? '').' · '.($row->snapshot['plate'] ?? '')), 'driver' => $row->snapshot['driver'] ?? null, 'helpers' => $row->snapshot['helpers'] ?? []];
    }

    private function newValue(array $state): array
    {
        return ['shift' => $state['shift']->name, 'vehicle' => trim($state['vehicle']->name.' · '.$state['vehicle']->plate), 'driver_id' => $state['driver_id'], 'helper_ids' => $state['helper_ids']];
    }

    private function historyValue(Programming $row, array $data): array
    {
        return ['id' => $this->oldId($row, $data), 'value' => $this->oldValue($row, $data)];
    }

    private function historyAfter(Programming $row, array $data): array
    {
        $value = match ($data['type']) {
            'shift' => ['id' => $data['new_shift_id'], 'value' => $row->snapshot['shift'] ?? null],
            'driver' => ['id' => $data['new_driver_id'], 'value' => $row->snapshot['driver'] ?? null],
            'helper' => ['id' => $data['new_employee_id'], 'value' => $this->employeeName(Employee::findOrFail($data['new_employee_id']))],
            'vehicle' => ['id' => $data['new_vehicle_id'], 'value' => trim(($row->snapshot['vehicle'] ?? '').' · '.($row->snapshot['plate'] ?? ''))],
        };
        return [...$value, 'reason' => $data['reason'], 'custom_reason' => $data['custom_reason'] ?? null, 'description' => $data['description'] ?? null];
    }

    private function oldId(Programming $row, array $data): int
    {
        return match ($data['type']) { 'shift' => (int) $row->shift_id, 'driver' => (int) $row->driver_id, 'helper' => (int) $data['old_employee_id'], default => (int) $row->vehicle_id };
    }

    private function oldValue(Programming $row, array $data): mixed
    {
        return match ($data['type']) {
            'shift' => $row->snapshot['shift'] ?? null,
            'driver' => $row->snapshot['driver'] ?? null,
            'helper' => $this->employeeName($row->employees->firstWhere('id', (int) $data['old_employee_id'])),
            default => trim(($row->snapshot['vehicle'] ?? '').' · '.($row->snapshot['plate'] ?? '')),
        };
    }

    private function newShiftId(array $data): ?int { return $data['type'] === 'shift' ? (int) $data['new_shift_id'] : null; }
    private function newVehicleId(array $data): ?int { return $data['type'] === 'vehicle' ? (int) $data['new_vehicle_id'] : null; }
    private function newEmployeeId(array $data): ?int { return match ($data['type']) { 'driver' => (int) $data['new_driver_id'], 'helper' => (int) $data['new_employee_id'], default => null }; }

    private function employeeName(Employee $employee): string { return trim($employee->first_name.' '.$employee->last_name); }
    private function typeLabel(string $type): string { return ['shift' => 'turno', 'driver' => 'conductor', 'helper' => 'ayudante', 'vehicle' => 'vehículo'][$type]; }
    private function reject(string $message): never { throw ValidationException::withMessages(['bulk_modifications' => $message]); }
}
