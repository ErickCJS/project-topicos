<?php

namespace App\Services;

use App\Models\admin\Attendance;
use App\Models\admin\Employee;
use App\Models\admin\Holiday;
use App\Models\admin\Programming;
use App\Models\admin\Reason;
use App\Models\admin\Shift;
use App\Models\admin\StaffGroup;
use App\Models\admin\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProgrammingReschedulingService
{
    public function __construct(private ProgrammingService $programmingService) {}

    public function options(Programming $programming, ?string $date = null, ?int $shiftId = null): array
    {
        $this->assertOrigin($programming);
        $programming->loadMissing('employees');
        $employees = collect();
        $vehicles = collect();
        if ($date && $shiftId) {
            $day = CarbonImmutable::parse($date)->toDateString();
            $shift = Shift::findOrFail($shiftId);
            $rows = Programming::with(['employees', 'shift'])->whereKeyNot($programming->id)
                ->where('status', '!=', Programming::STATUS_CANCELLED)
                ->whereDate('date', '>=', CarbonImmutable::parse($day)->subDay())
                ->whereDate('date', '<=', CarbonImmutable::parse($day)->addDay())->get();
            $conflicts = $rows->filter(fn (Programming $row) => $this->overlap(CarbonImmutable::parse($day), $shift, $row->date, $row->shift));
            $busyIds = $conflicts->flatMap(fn (Programming $row) => $row->employees->pluck('id'))->map(fn ($id) => (int) $id)->unique();
            $employees = Employee::with(['contracts', 'vacations'])->where('status', 'Activo')->orderBy('last_name')->orderBy('first_name')->get()
                ->filter(fn (Employee $employee) => ! $busyIds->contains((int) $employee->id)
                    && $employee->contracts->contains(fn ($contract) => $contract->is_active && ! $contract->cancelled_at && $contract->start_date->toDateString() <= $day && (! $contract->end_date || $contract->end_date->toDateString() >= $day))
                    && ! $employee->vacations->contains(fn ($vacation) => $vacation->status === 'Aprobada' && $vacation->start_date && $vacation->end_date && $vacation->start_date->toDateString() <= $day && $vacation->end_date->toDateString() >= $day))
                ->map(fn (Employee $employee) => ['id' => $employee->id, 'name' => $this->programmingService->name($employee), 'dni' => $employee->dni, 'is_driver' => (int) $employee->employee_type_id === 1])->values();
            $vehicles = Vehicle::orderBy('name')->get(['id', 'name', 'code', 'plate', 'occupats_capacity'])
                ->filter(fn ($vehicle) => (int) $vehicle->occupats_capacity >= $programming->employees->unique('id')->count()
                    && ! $conflicts->contains(fn (Programming $row) => (int) $row->vehicle_id === (int) $vehicle->id))->values();
        }

        return [
            'origin' => $this->summary($programming),
            'shifts' => Shift::orderBy('name')->get(['id', 'name', 'start_time', 'end_time']),
            'vehicles' => $vehicles,
            'drivers' => $employees->where('is_driver', true)->values(),
            'helpers' => $employees->where('is_driver', false)->values(),
            'reasons' => Reason::orderBy('name')->get(['id', 'name']),
        ];
    }

    public function preview(Programming $origin, array $data): array
    {
        $origin->loadMissing(['employees', 'shift', 'vehicle', 'parentProgramming']);
        $this->assertOrigin($origin);
        $this->validateInput($origin, $data);
        $issues = $this->issues($origin, $data);
        $destination = $this->destinationSummary($origin, $data);

        return ['valid' => $issues === [], 'issues' => $issues, 'origin' => $this->summary($origin), 'destination' => $destination];
    }

    public function confirm(Programming $origin, array $data, int $userId): array
    {
        try {
            return DB::transaction(function () use ($origin, $data, $userId) {
            $origin = Programming::whereKey($origin->id)->lockForUpdate()->firstOrFail();
            $this->assertOrigin($origin);
            $this->validateInput($origin, $data);
            $issues = $this->issues($origin, $data);
            if ($issues) {
                $this->reject('La disponibilidad cambió. Vuelva a validar antes de confirmar.');
            }

            $group = StaffGroup::with(['zone', 'shift'])->whereKey($origin->staff_group_id)->lockForUpdate()->firstOrFail();
            if (! $group->state) {
                $this->reject('No se puede reprogramar con un grupo inactivo.');
            }
            $vehicle = Vehicle::whereKey($data['vehicle_id'])->lockForUpdate()->firstOrFail();
            $employeeIds = array_merge([(int) $data['driver_id']], array_map('intval', $data['helpers']));
            Employee::whereIn('id', $employeeIds)->orderBy('id')->lockForUpdate()->get();
            $origin->load(['employees', 'shift', 'vehicle']);
            if ($origin->hasChildProgramming()) {
                $this->reject('El recorrido ya tiene un destino directo.');
            }

            $reason = Reason::findOrFail($data['reason_id']);
            $people = Employee::whereIn('id', $employeeIds)->get()->keyBy('id');
            $snapshot = $this->snapshot($origin, $group, $vehicle, $people, $data);
            $operationId = (string) Str::uuid();
            $destination = new Programming;
            $destination->fill([
                'staff_group_id' => $origin->staff_group_id,
                'zone_id' => $origin->zone_id,
                'shift_id' => $data['shift_id'],
                'vehicle_id' => $vehicle->id,
                'driver_id' => $data['driver_id'],
                'date' => $data['destination_date'],
                'status' => Programming::STATUS_PROGRAMMED,
                'notes' => $data['notes'] ?? $origin->notes,
                'snapshot' => $snapshot,
                'parent_programming_id' => $origin->id,
                'group_date_key' => $origin->staff_group_id.':'.$data['destination_date'],
                'vehicle_date_key' => $data['destination_date'].':'.$data['shift_id'].':'.$vehicle->id,
                'created_by' => $userId,
            ]);
            $destination->save();
            $destination->employees()->attach(array_reduce($employeeIds, function (array $carry, int $id) use ($data) {
                $carry[$id] = ['date' => $data['destination_date'], 'shift_id' => $data['shift_id'], 'role' => $id === (int) $data['driver_id'] ? 'driver' : 'helper'];
                return $carry;
            }, []));

            $before = $origin->toArray();
            $releasedCount = $origin->employees->count();
            $origin->employees()->detach();
            $origin->update(['status' => Programming::STATUS_RESCHEDULED, 'group_date_key' => null, 'vehicle_date_key' => null]);
            $afterOrigin = $origin->fresh()->toArray();
            $afterDestination = $destination->fresh()->toArray();
            $common = ['user_id' => $userId, 'operation_id' => $operationId, 'reason' => $reason->name, 'description' => $data['description'] ?? null, 'origin_programming_id' => $origin->id, 'destination_programming_id' => $destination->id, 'resources' => ['released' => $releasedCount, 'assigned' => count($employeeIds)]];
            $origin->changes()->create($common + ['action' => 'Reprogramación', 'before' => $before, 'after' => $afterOrigin]);
            $destination->changes()->create($common + ['action' => 'Creación por reprogramación', 'before' => null, 'after' => $afterDestination]);

            return ['operation_id' => $operationId, 'origin_id' => $origin->id, 'destination_id' => $destination->id];
            }, 3);
        } catch (QueryException $exception) {
            // Unique reservations are the last concurrency guard. Convert the
            // database error into a safe, actionable validation message.
            if (in_array((string) $exception->getCode(), ['23000', '23505', '1062'], true)) {
                $this->reject('La fecha destino o alguno de los recursos ya fue reservado. Vuelva a validar antes de confirmar.');
            }

            throw $exception;
        }
    }

    public function related(Programming $programming): array
    {
        $programming->load(['parentProgramming', 'childProgrammings']);
        $root = $programming;
        while ($root->parentProgramming) {
            $root = $root->parentProgramming;
        }
        $chain = collect();
        while ($root) {
            $chain->push($this->summary($root));
            $root = $root->childProgrammings()->first();
        }
        return ['data' => $chain->values()];
    }

    private function validateInput(Programming $origin, array $data): void
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $data['destination_date'], 'America/Lima');
        $today = CarbonImmutable::now('America/Lima')->startOfDay();
        if ($date->toDateString() === $origin->date->toDateString() || $date->lt($today)) $this->reject('La fecha destino debe ser diferente y no puede estar en el pasado.');
        $group = StaffGroup::with('days')->findOrFail($origin->staff_group_id);
        if (! $group->state) $this->reject('El grupo seleccionado está inactivo.');
        if (! in_array($date->dayOfWeekIso, $group->days->pluck('day')->map(fn ($day) => (int) $day)->all(), true)) $this->reject('La fecha destino no corresponde a un día permitido por el grupo.');
        if (Holiday::where('state', true)->whereDate('date', $date->toDateString())->exists()) $this->reject('La fecha destino es feriado y no está habilitada para este recorrido.');
        $reason = Reason::find($data['reason_id']);
        if (! $reason && ! empty($data['reason'])) {
            $reason = Reason::where('name', $data['reason'])->first();
        }
        if (! $reason) $this->reject('El motivo seleccionado no existe.');
        $ids = array_merge([(int) $data['driver_id']], array_map('intval', $data['helpers']));
        if (count($ids) !== count(array_unique($ids))) $this->reject('Una persona no puede ocupar dos plazas del mismo recorrido.');
        $vehicle = Vehicle::findOrFail($data['vehicle_id']);
        if (count($ids) > (int) $vehicle->occupats_capacity) $this->reject('El equipo supera la capacidad del vehículo.');
        $people = Employee::with(['contracts', 'vacations'])->whereIn('id', $ids)->get()->keyBy('id');
        foreach ($ids as $id) {
            $person = $people->get($id);
            if (! $person || $person->status !== 'Activo') $this->reject('El personal seleccionado debe existir y estar activo.');
            if (($id === (int) $data['driver_id']) !== ((int) $person->employee_type_id === 1)) $this->reject('El conductor y los ayudantes deben tener el cargo correspondiente.');
            if (! $person->contracts->contains(fn ($c) => $c->is_active && ! $c->cancelled_at && $c->start_date->toDateString() <= $date->toDateString() && (! $c->end_date || $c->end_date->toDateString() >= $date->toDateString()))) $this->reject('El personal seleccionado no tiene contrato vigente en la fecha destino.');
            if ($person->vacations->contains(fn ($v) => $v->status === 'Aprobada' && $v->start_date <= $date && $v->end_date >= $date)) $this->reject('El personal seleccionado tiene vacaciones aprobadas en la fecha destino.');
        }
    }

    private function issues(Programming $origin, array $data): array
    {
        $candidate = Shift::findOrFail($data['shift_id']);
        $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $data['destination_date'], 'America/Lima');
        $ids = array_merge([(int) $data['driver_id']], array_map('intval', $data['helpers']));
        $rows = Programming::with(['employees', 'shift'])
            ->whereKeyNot($origin->id)
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->whereDate('date', '>=', $date->subDay())
            ->whereDate('date', '<=', $date->addDay())
            ->get();
        $issues = [];
        $groupReservation = Programming::query()
            ->whereKeyNot($origin->id)
            ->where('staff_group_id', $origin->staff_group_id)
            ->whereDate('date', $date->toDateString())
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->whereNotNull('group_date_key')
            ->first();
        if ($groupReservation) {
            $issues[] = ['resource' => 'group', 'programming_id' => $groupReservation->id, 'message' => 'El grupo ya tiene un recorrido reservado en la fecha destino.'];
        }
        foreach ($rows as $row) {
            if ($this->overlap($date, $candidate, $row->date, $row->shift)) {
                if ($row->vehicle_id === (int) $data['vehicle_id']) $issues[] = ['resource' => 'vehicle', 'programming_id' => $row->id, 'message' => 'El vehículo ya está ocupado en el intervalo seleccionado.'];
                $busy = $row->employees->pluck('id')->intersect($ids);
                if ($busy->isNotEmpty()) $issues[] = ['resource' => 'employee', 'programming_id' => $row->id, 'employee_ids' => $busy->values(), 'message' => 'Hay personal asignado en conflicto, incluido un posible turno nocturno.'];
            }
        }
        if (Attendance::whereDate('date', $origin->date)->where('shift_id', $origin->shift_id)->whereIn('employee_id', $origin->employees()->pluck('employees.id'))->exists()) $issues[] = ['resource' => 'attendance', 'message' => 'El recorrido original tiene asistencias registradas.'];
        return $issues;
    }

    private function overlap($dateA, Shift $a, $dateB, Shift $b): bool
    {
        $startA = CarbonImmutable::parse($dateA->toDateString().' '.$a->start_time); $endA = CarbonImmutable::parse($dateA->toDateString().' '.$a->end_time);
        $startB = CarbonImmutable::parse($dateB->toDateString().' '.$b->start_time); $endB = CarbonImmutable::parse($dateB->toDateString().' '.$b->end_time);
        if ($endA <= $startA) $endA = $endA->addDay(); if ($endB <= $startB) $endB = $endB->addDay();
        return $startA < $endB && $startB < $endA;
    }

    private function snapshot(Programming $origin, StaffGroup $group, Vehicle $vehicle, $people, array $data): array
    {
        $shift = Shift::findOrFail($data['shift_id']);
        return ['group' => $origin->snapshot['group'] ?? $group->name, 'zone' => $origin->snapshot['zone'] ?? $group->zone->name, 'shift' => $shift->name, 'hours' => substr($shift->start_time, 0, 5).' - '.substr($shift->end_time, 0, 5), 'vehicle' => $vehicle->name, 'plate' => $vehicle->plate, 'driver' => $this->programmingService->name($people[$data['driver_id']]), 'helpers' => collect($data['helpers'])->map(fn ($id) => $this->programmingService->name($people[$id]))->values()->all(), 'rescheduled_from' => $origin->id];
    }

    private function destinationSummary(Programming $origin, array $data): array
    {
        return ['date' => (string) $data['destination_date'], 'shift_id' => $data['shift_id'], 'vehicle_id' => $data['vehicle_id'], 'driver_id' => $data['driver_id'], 'helper_ids' => array_values($data['helpers']), 'notes' => $data['notes'] ?? $origin->notes];
    }

    private function summary(Programming $programming): array
    { return ['id' => $programming->id, 'date' => $programming->date->toDateString(), 'status' => $programming->status, 'parent_programming_id' => $programming->parent_programming_id, 'child_programming_id' => $programming->childProgrammings()->value('id'), 'group' => $programming->snapshot['group'] ?? null, 'zone' => $programming->snapshot['zone'] ?? null, 'notes' => $programming->notes]; }

    private function assertOrigin(Programming $programming): void
    { if ($programming->status !== Programming::STATUS_PROGRAMMED) $this->reject('Solo se puede reprogramar un recorrido programado.'); }

    private function reject(string $message): never
    { throw ValidationException::withMessages(['rescheduling' => $message]); }
}
