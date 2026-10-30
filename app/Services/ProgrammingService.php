<?php

namespace App\Services;

use App\Models\admin\Employee;
use App\Models\admin\Holiday;
use App\Models\admin\Programming;
use App\Models\admin\StaffGroup;
use App\Models\admin\Vehicle;
use App\Models\User;
use Carbon\CarbonPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgrammingService
{
    public function inspect(array $data, ?Programming $editing = null): array
    {
        $group = StaffGroup::with(['zone', 'shift', 'vehicle', 'days', 'driver', 'helpers'])->findOrFail($data['staff_group_id']);
        if (! $group->state) {
            $this->reject('El grupo seleccionado está inactivo.');
        }
        if ($editing && $editing->status !== 'Programado') {
            $this->reject('Solo se puede modificar una programación pendiente.');
        }
        if ($editing && $data['start_date'] !== $data['end_date']) {
            $this->reject('La modificación corresponde a un solo recorrido diario.');
        }
        if ($editing && ! in_array(CarbonImmutable::parse($data['start_date'])->dayOfWeekIso, array_map('intval', $data['days']), true)) {
            $this->reject('El día de trabajo seleccionado no corresponde a la nueva fecha. Seleccione el día correspondiente y valide nuevamente.');
        }
        $helpers = array_map('intval', $data['helpers'] ?? []);
        $ids = array_merge([(int) $data['driver_id']], $helpers);
        if (count(array_unique($ids)) !== count($ids)) {
            $this->reject('Una persona no puede ocupar dos plazas del mismo equipo.');
        }
        if (count($ids) > (int) $group->vehicle->occupats_capacity) {
            $this->reject('El equipo supera la capacidad de ocupantes del vehículo.');
        }
        $people = Employee::with(['contracts', 'vacations'])->whereIn('id', $ids)->get()->keyBy('id');
        foreach ($ids as $id) {
            $person = $people->get($id);
            if (! $person || $person->status !== 'Activo') {
                $this->reject('El personal seleccionado debe existir y estar activo.');
            }
            // Same role convention as StaffGroupsController.
            if (($id === (int) $data['driver_id']) !== ((int) $person->employee_type_id === 1)) {
                $this->reject('El conductor y los ayudantes deben tener el cargo correspondiente.');
            }
        }
        $holidayRecords = Holiday::where('state', true)->whereDate('date', '>=', $data['start_date'])->whereDate('date', '<=', $data['end_date'])->get();
        $holidays = $holidayRecords->pluck('date')->map(fn ($date) => $date->toDateString())->unique()->values()->all();
        $workHolidays = $data['work_holidays'] ?? [];
        foreach ($workHolidays as $date) {
            if (! in_array($date, $holidays, true) || ! in_array(CarbonImmutable::parse($date)->dayOfWeekIso, array_map('intval', $data['days']), true)) {
                $this->reject('Los feriados seleccionados cambiaron o no corresponden al período y días elegidos. Actualice la selección y valide nuevamente.');
            }
        }
        $duplicates = Programming::where('staff_group_id', $group->id)
            ->when($editing, fn ($query) => $query->whereKeyNot($editing->id))
            ->whereDate('date', '>=', $data['start_date'])->whereDate('date', '<=', $data['end_date'])->pluck('date')
            ->map(fn ($date) => substr((string) $date, 0, 10))->all();
        $dates = [];
        $omittedHolidays = [];
        $omittedDuplicates = [];
        $includedHolidays = [];
        foreach (CarbonPeriod::create($data['start_date'], $data['end_date']) as $day) {
            if (! in_array($day->dayOfWeekIso, array_map('intval', $data['days']), true)) {
                continue;
            }
            $date = $day->toDateString();
            if (in_array($date, $duplicates, true)) {
                $omittedDuplicates[] = $date;
            } elseif (in_array($date, $holidays, true) && ! in_array($date, $workHolidays, true)) {
                $omittedHolidays[] = $date;
            } else {
                $dates[] = $date;
                if (in_array($date, $workHolidays, true)) {
                    $includedHolidays[] = $date;
                }
            }
        }
        // Editing checks reservations on the destination even before a holiday is enabled.
        $reservationDates = $editing ? [$data['start_date']] : $dates;
        $occupied = Programming::with('employees')->where('shift_id', $group->shift_id)->whereIn('date', $reservationDates)
            ->when($editing, fn ($query) => $query->whereKeyNot($editing->id))->get();
        $authorNames = User::whereIn('id', $occupied->map(fn ($row) => $row->snapshot['holiday_work']['authorized_by'] ?? null)->filter())->pluck('name', 'id');
        $routeContext = fn ($row) => [
            'id' => $row->id, 'group' => $row->snapshot['group'], 'shift' => $row->snapshot['shift'],
            'holiday_authorized' => ! empty($row->snapshot['holiday_work']),
            'authorized_by' => $authorNames[$row->snapshot['holiday_work']['authorized_by'] ?? 0] ?? null,
        ];
        $issues = [];
        if ($editing && $omittedDuplicates) {
            $issues[] = ['person' => $group->name, 'reason' => 'Ya existe un recorrido de este grupo en la fecha seleccionada',
                'dates' => $omittedDuplicates, 'routes' => $occupied->where('staff_group_id', $group->id)->map($routeContext)->values()->all()];
        }
        foreach ($people as $person) {
            foreach (['contract' => 'Sin contrato vigente', 'vacation' => 'Vacaciones aprobadas', 'conflict' => 'Asignación en la misma fecha y turno'] as $kind => $reason) {
                $affected = array_values(array_filter($kind === 'conflict' ? $reservationDates : $dates, function ($date) use ($person, $kind, $occupied) {
                    return match ($kind) {
                        'contract' => ! $this->hasContract($person, $date),
                        'vacation' => $this->onVacation($person, $date),
                        default => $occupied->contains(fn ($row) => $row->date->toDateString() === $date && $row->employees->contains('id', $person->id)),
                    };
                }));
                if ($affected) {
                    $issue = ['employee_id' => $person->id, 'person' => $this->name($person), 'reason' => $reason, 'dates' => $affected];
                    if ($kind === 'conflict') {
                        $issue['routes'] = $occupied->filter(fn ($row) => $row->employees->contains('id', $person->id))->map($routeContext)->values()->all();
                    }
                    $issues[] = $issue;
                }
            }
        }
        $vehicleDates = $occupied->where('vehicle_id', $group->vehicle_id)->pluck('date')->map(fn ($date) => $date->toDateString())->values()->all();
        if ($vehicleDates) {
            $issues[] = ['person' => $group->vehicle->name, 'reason' => 'Vehículo asignado en la misma fecha y turno', 'dates' => $vehicleDates,
                'routes' => $occupied->where('vehicle_id', $group->vehicle_id)->map($routeContext)->values()->all()];
        }
        $suggestions = [];
        if ($issues && $dates) {
            $candidates = Employee::with(['contracts', 'vacations'])->where('status', 'Activo')->whereNotIn('id', $ids)->get();
            foreach (collect($issues)->pluck('employee_id')->filter()->unique() as $id) {
                $role = (int) $people[$id]->employee_type_id === 1;
                $suggestions[$id] = $candidates->filter(function ($candidate) use ($role, $dates, $occupied) {
                    if (((int) $candidate->employee_type_id === 1) !== $role) {
                        return false;
                    }
                    foreach ($dates as $date) {
                        if (! $this->hasContract($candidate, $date) || $this->onVacation($candidate, $date) || $occupied->contains(fn ($row) => $row->date->toDateString() === $date && $row->employees->contains('id', $candidate->id))) {
                            return false;
                        }
                    }

                    return true;
                })->take(3)->map(fn ($person) => ['id' => $person->id, 'name' => $this->name($person)])->values()->all();
            }
        }

        return compact('group', 'people', 'dates', 'issues', 'suggestions', 'omittedHolidays', 'omittedDuplicates', 'includedHolidays', 'holidayRecords');
    }

    private function hasContract(Employee $person, string $date): bool
    {
        return $person->contracts->contains(fn ($contract) => $contract->is_active && ! $contract->cancelled_at
            && $contract->start_date->toDateString() <= $date
            && (! $contract->end_date || $contract->end_date->toDateString() >= $date));
    }

    private function onVacation(Employee $person, string $date): bool
    {
        return $person->vacations->contains(fn ($vacation) => $vacation->status === 'Aprobada' && $vacation->start_date && $vacation->end_date
            && $vacation->start_date->toDateString() <= $date && $vacation->end_date->toDateString() >= $date);
    }

    public function name(Employee $person): string
    {
        return trim($person->first_name.' '.$person->last_name);
    }

    public function groupFingerprint(StaffGroup $group): string
    {
        $group->loadMissing(['zone', 'shift', 'vehicle', 'days', 'driver', 'helpers']);

        return hash('sha256', json_encode($group->toArray(), JSON_THROW_ON_ERROR));
    }

    public function save(array $data, int $userId, ?Programming $editing = null, ?string $groupFingerprint = null): array
    {
        try {
            return DB::transaction(function () use ($data, $userId, $editing, $groupFingerprint) {
                // Serialize shared resources; unique reservation keys are a second concurrency guard.
                StaffGroup::whereKey($data['staff_group_id'])->lockForUpdate()->firstOrFail();
                if ($editing) {
                    $editing = Programming::whereKey($editing->id)->lockForUpdate()->firstOrFail();
                }
                $group = StaffGroup::findOrFail($data['staff_group_id']);
                Vehicle::whereKey($group->vehicle_id)->lockForUpdate()->firstOrFail();
                Employee::whereIn('id', array_merge([$data['driver_id']], $data['helpers'] ?? []))->orderBy('id')->lockForUpdate()->get();
                $result = $this->inspect($data, $editing);
                if ($groupFingerprint && ! hash_equals($groupFingerprint, $this->groupFingerprint($result['group']))) {
                    $this->reject('La configuración del grupo cambió. Seleccione el grupo y valide nuevamente.');
                }
                if ($result['issues']) {
                    $this->reject('La disponibilidad cambió o existen inconsistencias. Valide nuevamente.');
                }
                if (! $result['dates']) {
                    $this->reject('No hay fechas nuevas para generar. Las fechas elegidas ya están programadas o fueron omitidas.');
                }
                $snapshot = [
                    'group' => $result['group']->name, 'zone' => $result['group']->zone->name,
                    'shift' => $result['group']->shift->name, 'hours' => substr($result['group']->shift->start_time, 0, 5).' - '.substr($result['group']->shift->end_time, 0, 5),
                    'vehicle' => $result['group']->vehicle->name, 'plate' => $result['group']->vehicle->plate,
                    'driver' => $this->name($result['people'][$data['driver_id']]),
                    'helpers' => collect($data['helpers'] ?? [])->map(fn ($id) => $this->name($result['people'][$id]))->values()->all(),
                ];
                foreach ($result['dates'] as $date) {
                    $dailySnapshot = $snapshot;
                    if (in_array($date, $result['includedHolidays'], true)) {
                        $dailySnapshot['holiday_work'] = [
                            'date' => $date,
                            'names' => $result['holidayRecords']->filter(fn ($holiday) => $holiday->date->toDateString() === $date)->pluck('name')->values()->all(),
                            'authorized_by' => $userId, 'authorized_at' => now()->toIso8601String(),
                        ];
                    }
                    $row = $editing ?? new Programming;
                    $before = $editing ? $row->toArray() : null;
                    $row->fill([
                        'staff_group_id' => $group->id, 'zone_id' => $group->zone_id, 'shift_id' => $group->shift_id,
                        'vehicle_id' => $group->vehicle_id, 'driver_id' => $data['driver_id'], 'date' => $date,
                        'notes' => $data['notes'] ?? null, 'snapshot' => $dailySnapshot,
                        'group_date_key' => $group->id.':'.$date, 'vehicle_date_key' => $date.':'.$group->shift_id.':'.$group->vehicle_id,
                    ]);
                    if (! $row->exists) {
                        $row->created_by = $userId;
                    }
                    $row->save();
                    $row->employees()->detach();
                    $assignments = [];
                    foreach (array_merge([$data['driver_id']], $data['helpers'] ?? []) as $id) {
                        $assignments[$id] = ['date' => $date, 'shift_id' => $group->shift_id, 'role' => (int) $id === (int) $data['driver_id'] ? 'driver' : 'helper'];
                    }
                    $row->employees()->attach($assignments);
                    $row->changes()->create(['user_id' => $userId, 'action' => $editing ? 'Modificación' : 'Creación', 'before' => $before, 'after' => $row->toArray()]);
                }

                return ['created' => count($result['dates']), 'omitted_holidays' => $result['omittedHolidays'], 'omitted_duplicates' => $result['omittedDuplicates'], 'included_holidays' => $result['includedHolidays']];
            }, 3);
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                $this->reject('Otro usuario reservó estas fechas o recursos. Valide nuevamente.');
            }
            throw $exception;
        }
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['programming' => $message]);
    }
}
