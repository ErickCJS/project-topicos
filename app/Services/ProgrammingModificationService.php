<?php

namespace App\Services;

use App\Models\admin\Employee;
use App\Models\admin\Programming;
use App\Models\admin\Reason;
use App\Models\admin\Shift;
use App\Models\admin\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgrammingModificationService
{
    public function options(Programming $programming, int $shiftId): array
    {
        $shift = Shift::findOrFail($shiftId);
        $conflictingRoutes = $this->overlappingRoutes($programming, $shift);
        $busyVehicles = $conflictingRoutes->pluck('vehicle_id')->map(fn ($id) => (int) $id)->all();
        $busyEmployees = $conflictingRoutes->flatMap(fn (Programming $route) => $route->employees->pluck('id'))
            ->map(fn ($id) => (int) $id)->unique()->values()->all();
        $currentHelperIds = $programming->employees()->wherePivot('role', 'helper')->pluck('employees.id')->map(fn ($id) => (int) $id)->all();
        $employeeQuery = Employee::query()->where('status', 'Activo')
            ->whereHas('contracts', fn ($query) => $query->where('is_active', true)->whereNull('cancelled_at')
                ->whereDate('start_date', '<=', $programming->date->toDateString())
                ->where(fn ($dates) => $dates->whereNull('end_date')->orWhereDate('end_date', '>=', $programming->date->toDateString())))
            ->whereDoesntHave('vacations', fn ($query) => $query->where('status', 'Aprobada')
                ->whereDate('start_date', '<=', $programming->date->toDateString())
                ->whereDate('end_date', '>=', $programming->date->toDateString()));

        $employees = (clone $employeeQuery)->with('employeeType')->orderBy('last_name')->orderBy('first_name')->get()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => trim($employee->first_name.' '.$employee->last_name),
                'dni' => $employee->dni,
                'role' => (int) $employee->employee_type_id === 1 ? 'driver' : 'helper',
                'available' => ! in_array((int) $employee->id, $busyEmployees, true),
            ]);

        $currentVehicleCapacity = (int) Vehicle::query()->whereKey($programming->vehicle_id)->value('occupats_capacity');
        $vehicles = Vehicle::query()->orderBy('name')->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'name' => $vehicle->name,
                'code' => $vehicle->code,
                'plate' => $vehicle->plate,
                'capacity' => (int) $vehicle->occupats_capacity,
                'available' => ! in_array((int) $vehicle->id, $busyVehicles, true),
            ])
            ->filter(fn (array $vehicle) => $vehicle['capacity'] === $currentVehicleCapacity)
            ->values();
        return [
            'shifts' => Shift::query()->orderBy('name')->get(['id', 'name', 'start_time', 'end_time']),
            'vehicles' => $vehicles,
            'drivers' => $employees->where('role', 'driver')->values(),
            'helpers' => $employees->where('role', 'helper')->values(),
            'reasons' => Reason::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function apply(Programming $programming, array $changes, int $userId): int
    {
        try {
            return DB::transaction(function () use ($programming, $changes, $userId) {
                $row = Programming::query()->whereKey($programming->id)->lockForUpdate()->firstOrFail();
                if ($row->status !== 'Programado') {
                    $this->reject('Solo se puede modificar un recorrido programado.');
                }

                $row->load('employees');
                $oldShiftId = (int) $row->shift_id;
                $oldVehicleId = (int) $row->vehicle_id;
                $oldVehicleCapacity = (int) Vehicle::query()->whereKey($oldVehicleId)->value('occupats_capacity');
                $oldDriverId = (int) $row->driver_id;
                $oldHelperIds = $row->employees->where('pivot.role', 'helper')->pluck('id')->map(fn ($id) => (int) $id)->all();

                $nextShiftId = $oldShiftId;
                $nextVehicleId = $oldVehicleId;
                $nextDriverId = $oldDriverId;
                $nextHelperIds = $oldHelperIds;
                $seen = [];
                $helperOldIds = [];
                foreach ($changes as $change) {
                    $type = $change['type'];
                    if (! in_array($type, ['shift', 'vehicle', 'driver', 'helper'], true)) {
                        $this->reject('El tipo de cambio seleccionado no está permitido.');
                    }
                    if ($type !== 'helper' && isset($seen[$type])) {
                        $this->reject('Solo se permite un cambio pendiente por cada tipo de elemento.');
                    }
                    $seen[$type] = true;
                    $this->validateReason($change);
                    $newId = isset($change['new_id']) ? (int) $change['new_id'] : null;

                    if ($type === 'shift') {
                        if ($newId === $oldShiftId) $this->reject('El nuevo turno debe ser distinto al turno actual.');
                        $nextShiftId = $newId;
                    } elseif ($type === 'vehicle') {
                        if ($newId === $oldVehicleId) $this->reject('El nuevo vehículo debe ser distinto al vehículo actual.');
                        $nextVehicleId = $newId;
                    } elseif ($type === 'driver') {
                        if ($newId === $oldDriverId) $this->reject('El nuevo conductor debe ser distinto al conductor actual.');
                        $nextDriverId = $newId;
                    } elseif ($type === 'helper') {
                        $oldId = (int) $change['old_id'];
                        if (! in_array($oldId, $oldHelperIds, true) || in_array($oldId, $helperOldIds, true)) {
                            $this->reject('Seleccione un ayudante actual válido para reemplazar.');
                        }
                        if ($newId === $oldId || in_array($newId, $nextHelperIds, true)) {
                            $this->reject('El nuevo ayudante debe ser distinto y no estar asignado al equipo.');
                        }
                        $helperOldIds[] = $oldId;
                        $helperIndex = array_search($oldId, $nextHelperIds, true);
                        $nextHelperIds[$helperIndex] = $newId;
                    }
                }

                $shift = Shift::query()->whereKey($nextShiftId)->lockForUpdate()->firstOrFail();
                $vehicle = Vehicle::query()->whereKey($nextVehicleId)->lockForUpdate()->firstOrFail();
                if ($nextVehicleId !== $oldVehicleId && (int) $vehicle->occupats_capacity !== $oldVehicleCapacity) {
                    $this->reject('No se puede aplicar el cambio: el vehículo elegido debe tener la misma capacidad de ocupantes que el actual ('.$oldVehicleCapacity.' ocupantes). Seleccione otro vehículo.');
                }
                $employeeIds = array_values(array_unique(array_merge([$nextDriverId], $nextHelperIds)));
                $employees = Employee::query()->whereIn('id', $employeeIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                $this->validateAvailability($row, $shift, $vehicle, $employees, $nextDriverId, $nextHelperIds);

                $snapshot = $row->snapshot;
                $beforeValues = [
                    'shift' => ['id' => $oldShiftId, 'value' => $snapshot['shift']],
                    'vehicle' => ['id' => $oldVehicleId, 'value' => trim(($snapshot['vehicle'] ?? '').' · '.($snapshot['plate'] ?? ''))],
                    'driver' => ['id' => $oldDriverId, 'value' => $snapshot['driver']],
                ];
                foreach ($oldHelperIds as $id) {
                    $employee = $row->employees->firstWhere('id', $id);
                    $beforeValues['helper:'.$id] = ['id' => $id, 'value' => trim($employee->first_name.' '.$employee->last_name)];
                }

                $row->shift_id = $nextShiftId;
                $row->vehicle_id = $nextVehicleId;
                $row->driver_id = $nextDriverId;
                $row->snapshot = [
                    ...$snapshot,
                    'shift' => $shift->name,
                    'hours' => substr($shift->start_time, 0, 5).' - '.substr($shift->end_time, 0, 5),
                    'vehicle' => $vehicle->name,
                    'plate' => $vehicle->plate,
                    'driver' => $this->employeeName($employees[$nextDriverId]),
                    'helpers' => collect($nextHelperIds)->map(fn ($id) => $this->employeeName($employees[$id]))->values()->all(),
                ];
                $row->vehicle_date_key = $row->date->toDateString().':'.$nextShiftId.':'.$nextVehicleId;
                $row->save();
                $row->employees()->detach();
                $assignments = [];
                foreach (array_merge([$nextDriverId], $nextHelperIds) as $employeeId) {
                    $assignments[$employeeId] = [
                        'date' => $row->date->toDateString(),
                        'shift_id' => $nextShiftId,
                        'role' => $employeeId === $nextDriverId ? 'driver' : 'helper',
                    ];
                }
                $row->employees()->attach($assignments);

                foreach ($changes as $change) {
                    $type = $change['type'];
                    $isHelperChange = $type === 'helper';
                    $oldId = $isHelperChange ? (int) $change['old_id'] : $beforeValues[$type]['id'];
                    $newId = isset($change['new_id']) ? (int) $change['new_id'] : null;
                    $afterValue = match ($type) {
                        'shift' => $shift->name,
                        'vehicle' => trim($vehicle->name.' · '.$vehicle->plate),
                        default => $this->employeeName($employees[$newId]),
                    };
                    $beforeValue = $beforeValues[$isHelperChange ? 'helper:'.$oldId : $type];
                    $row->changes()->create([
                        'user_id' => $userId,
                        'action' => 'Cambio de '.$this->typeLabel($type),
                        'before' => $beforeValue,
                        'after' => [
                            'id' => $newId,
                            'value' => $afterValue,
                            'reason' => $change['reason'],
                            'custom_reason' => $change['custom_reason'] ?? null,
                            'description' => $change['description'] ?? null,
                        ],
                    ]);
                }

                return count($changes);
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                $this->reject('No se pudo aplicar el cambio porque el turno, vehículo o personal seleccionado acaba de ser reservado en otro recorrido. Actualice la lista y seleccione una opción disponible.');
            }
            throw $exception;
        }
    }

    private function validateAvailability(Programming $row, Shift $shift, Vehicle $vehicle, Collection $employees, int $driverId, array $helperIds): void
    {
        $date = $row->date->toDateString();
        $ids = array_merge([$driverId], $helperIds);
        if (count($ids) !== count(array_unique($ids))) {
            $this->reject('Una persona no puede ocupar dos cargos en la programación.');
        }
        if (count($ids) > (int) $vehicle->occupats_capacity) {
            $this->reject('El nuevo vehículo no tiene capacidad suficiente para el personal asignado.');
        }
        $conflictingRoutes = $this->overlappingRoutes($row, $shift);
        if ($conflictingRoutes->contains(fn (Programming $route) => (int) $route->vehicle_id === (int) $vehicle->id)) {
            $this->reject('No se puede aplicar el cambio: el vehículo seleccionado ya está asignado a otro recorrido cuyo horario se cruza con este servicio. Elija otro vehículo disponible.');
        }

        foreach ($ids as $employeeId) {
            $employee = $employees->get($employeeId);
            if (! $employee || $employee->status !== 'Activo'
                || (((int) $employeeId === $driverId) !== ((int) $employee->employee_type_id === 1))) {
                $this->reject('Seleccione personal activo con el cargo correspondiente.');
            }
            $hasContract = $employee->contracts()->where('is_active', true)->whereNull('cancelled_at')
                ->whereDate('start_date', '<=', $date)
                ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $date))->exists();
            if (! $hasContract) {
                $this->reject('El personal seleccionado no tiene contrato vigente en la fecha del recorrido.');
            }
            $onVacation = $employee->vacations()->where('status', 'Aprobada')
                ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->exists();
            if ($onVacation) {
                $this->reject('El personal seleccionado tiene vacaciones aprobadas en la fecha del recorrido.');
            }
            $busy = $conflictingRoutes->contains(
                fn (Programming $route) => $route->employees->contains('id', $employeeId)
            );
            if ($busy) {
                $this->reject('Una de las personas seleccionadas ya está asignada en un turno que se cruza con este servicio.');
            }
        }
    }

    private function overlappingRoutes(Programming $programming, Shift $targetShift): Collection
    {
        $date = CarbonImmutable::parse($programming->date->toDateString());
        [$targetStart, $targetEnd] = $this->interval($date, $targetShift);

        return Programming::query()
            ->with(['shift:id,start_time,end_time', 'employees:id'])
            ->where('status', '!=', 'Anulado')
            ->whereDate('date', '>=', $date->subDay()->toDateString())
            ->whereDate('date', '<=', $date->toDateString())
            ->whereKeyNot($programming->id)
            ->get()
            ->filter(function (Programming $route) use ($targetStart, $targetEnd): bool {
                if (! $route->shift) {
                    return false;
                }
                [$routeStart, $routeEnd] = $this->interval(
                    CarbonImmutable::parse($route->date->toDateString()),
                    $route->shift,
                );

                return $routeStart < $targetEnd && $targetStart < $routeEnd;
            })->values();
    }

    private function interval(CarbonImmutable $date, Shift $shift): array
    {
        $start = CarbonImmutable::parse($date->toDateString().' '.$shift->start_time);
        $end = CarbonImmutable::parse($date->toDateString().' '.$shift->end_time);
        if ($end <= $start) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    private function validateReason(array $change): void
    {
        if (! Reason::query()->where('name', $change['reason'])->exists()) {
            $this->reject('El motivo seleccionado ya no está disponible. Actualice el catálogo y vuelva a intentar.');
        }
        if ($change['reason'] === 'Otro' && trim($change['custom_reason'] ?? '') === '') {
            $this->reject('Especifique el motivo cuando seleccione “Otro”.');
        }
    }

    private function employeeName(Employee $employee): string
    {
        return trim($employee->first_name.' '.$employee->last_name);
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'shift' => 'turno',
            'vehicle' => 'vehículo',
            'driver' => 'conductor',
            'remove_helper' => 'ayudante retirado',
            default => 'ayudante',
        };
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['modifications' => $message]);
    }
}
