<?php

namespace App\Services;

use App\Models\admin\Attendance;
use App\Models\admin\Shift;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AttendanceMarkingRules
{
    public const ENTRY_ADVANCE_MINUTES = 15;

    /**
     * Find an open entry from today or an overnight shift that started yesterday.
     * A pending exit always has priority over opening another shift.
     */
    public function pendingExits(int $employeeId, CarbonImmutable $now): Collection
    {
        $today = $now->toDateString();
        $yesterday = $now->subDay()->toDateString();

        $sameDay = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('date', $today)
            ->where('type', 'Entrada')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('attendances as exits')
                    ->whereColumn('exits.employee_id', 'attendances.employee_id')
                    ->whereColumn('exits.shift_id', 'attendances.shift_id')
                    ->whereColumn('exits.date', 'attendances.date')
                    ->where('exits.type', 'Salida');
            })
            ->get();

        $overnight = Attendance::query()
            ->with('shift')
            ->where('employee_id', $employeeId)
            ->whereDate('date', $yesterday)
            ->where('type', 'Entrada')
            ->whereHas('shift', fn ($query) => $query->whereColumn('shifts.end_time', '<=', 'shifts.start_time'))
            ->whereNotExists(function ($query) use ($yesterday, $today) {
                $query->selectRaw('1')
                    ->from('attendances as exits')
                    ->whereColumn('exits.employee_id', 'attendances.employee_id')
                    ->whereColumn('exits.shift_id', 'attendances.shift_id')
                    ->whereDate('exits.date', '>=', $yesterday)
                    ->whereDate('exits.date', '<=', $today)
                    ->where('exits.type', 'Salida');
            })
            ->get();

        return $sameDay->concat($overnight)->unique('id')->values();
    }

    /**
     * Resolve the latest shift whose entry cutoff (start minus 15 minutes)
     * has already been reached. The returned date is the calendar date on
     * which that shift starts, which matters for overnight shifts.
     *
     * @return array{shift: Shift, start_date: string}
     */
    public function resolveShift(CarbonImmutable $now, ?int $selectedShiftId = null): array
    {
        $shifts = Shift::query()->orderBy('start_time')->get();

        if ($shifts->isEmpty()) {
            throw ValidationException::withMessages([
                'attendance' => 'No hay turnos configurados para registrar la asistencia.',
            ]);
        }

        $candidates = collect();

        foreach ([$now->toDateString(), $now->subDay()->toDateString()] as $startDate) {
            foreach ($shifts as $shift) {
                $startsAt = CarbonImmutable::parse(
                    $startDate.' '.$shift->start_time,
                    $now->getTimezone()
                );
                $cutoff = $startsAt->subMinutes(self::ENTRY_ADVANCE_MINUTES);

                if ($cutoff->lessThanOrEqualTo($now)) {
                    $candidates->push([
                        'shift' => $shift,
                        'start_date' => $startDate,
                        'cutoff' => $cutoff,
                    ]);
                }
            }
        }

        if ($candidates->isEmpty()) {
            throw ValidationException::withMessages([
                'attendance' => 'No se pudo determinar un turno para la hora de marcación.',
            ]);
        }

        $latestCutoff = $candidates->max(fn (array $item) => $item['cutoff']->getTimestamp());
        $matching = $candidates
            ->filter(fn (array $item) => $item['cutoff']->getTimestamp() === $latestCutoff)
            ->values();

        if ($selectedShiftId !== null) {
            $selected = $matching->first(
                fn (array $item) => (int) $item['shift']->id === $selectedShiftId
            );

            if ($selected === null) {
                throw $this->ambiguousShift($matching, 'El turno seleccionado no corresponde al horario actual.');
            }

            return ['shift' => $selected['shift'], 'start_date' => $selected['start_date']];
        }

        if ($matching->count() > 1) {
            throw $this->ambiguousShift($matching);
        }

        $selected = $matching->first();

        return ['shift' => $selected['shift'], 'start_date' => $selected['start_date']];
    }

    public function entryStatus(Shift $shift, string $startDate, CarbonImmutable $markedAt): string
    {
        $startsAt = CarbonImmutable::parse(
            $startDate.' '.$shift->start_time,
            $markedAt->getTimezone()
        );

        return $markedAt->greaterThan($startsAt) ? 'Tardanza' : 'Presente';
    }

    private function ambiguousShift(Collection $candidates, string $message = 'Hay más de un turno con el mismo horario de marcación. Selecciona el turno correspondiente.'): ValidationException
    {
        $options = $candidates->map(fn (array $item) => [
            'id' => $item['shift']->id,
            'name' => $item['shift']->name,
            'start_time' => substr((string) $item['shift']->start_time, 0, 5),
            'end_time' => substr((string) $item['shift']->end_time, 0, 5),
        ])->values()->all();

        return ValidationException::withMessages([
            'attendance' => $message,
            'shift_options' => [$options],
        ]);
    }
}
