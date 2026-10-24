<?php

namespace App\Services;

use App\Models\admin\Contract;
use App\Models\admin\Employee;
use App\Models\admin\Vacation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacationService
{
    public function create(array $data): Vacation
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::whereKey($data['employee_id'])->lockForUpdate()->firstOrFail();
            [$start, $end, $days] = $this->period($data);
            $this->assertCurrentEligibility($employee, $start, $end);
            $this->lockAnnualRecords($employee->id, (int) $data['year']);
            $this->assertAvailable($employee->id, (int) $data['year'], $start, $end, $days);

            $vacation = Vacation::create([
                'employee_id' => $employee->id,
                'year' => (int) $data['year'],
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'scheduled_days' => $days,
                'pending_days' => 0,
            ]);
            $this->syncAnnualBalance($employee->id, (int) $data['year']);

            return $vacation->fresh();
        }, 5);
    }

    public function update(Vacation $original, array $data): Vacation
    {
        return DB::transaction(function () use ($original, $data) {
            $employee = Employee::whereKey($original->employee_id)->lockForUpdate()->firstOrFail();
            $vacation = Vacation::whereKey($original->id)->lockForUpdate()->firstOrFail();

            if ((int) $data['employee_id'] !== $vacation->employee_id) {
                $this->reject('employee_id', 'El trabajador de una vacación registrada no se puede cambiar.');
            }

            [$start, $end, $days] = $this->period($data);
            if ($vacation->isHistorical()) {
                if ((int) $data['year'] !== $vacation->year) {
                    $this->reject('year', 'El año de un registro histórico no se puede cambiar durante su regularización.');
                }
                if ($days !== $vacation->scheduled_days) {
                    $this->reject(
                        'end_date',
                        'La regularización debe conservar los '.$vacation->scheduled_days.' días ya programados en el registro histórico.'
                    );
                }
                $this->assertHistoricalEligibility($employee, $start, $end);
            } else {
                $this->assertCurrentEligibility($employee, $start, $end);
            }

            $oldYear = $vacation->year;
            $newYear = (int) $data['year'];
            $this->lockAnnualRecords($employee->id, $oldYear);
            if ($newYear !== $oldYear) {
                $this->lockAnnualRecords($employee->id, $newYear);
            }
            $this->assertAvailable($employee->id, $newYear, $start, $end, $days, $vacation->id);

            $vacation->update([
                'year' => $newYear,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'scheduled_days' => $days,
            ]);

            $this->syncAnnualBalance($employee->id, $oldYear);
            if ($newYear !== $oldYear) {
                $this->syncAnnualBalance($employee->id, $newYear);
            }

            return $vacation->fresh();
        }, 5);
    }

    public function delete(Vacation $original): void
    {
        DB::transaction(function () use ($original) {
            Employee::whereKey($original->employee_id)->lockForUpdate()->firstOrFail();
            $vacation = Vacation::whereKey($original->id)->lockForUpdate()->firstOrFail();
            $employeeId = $vacation->employee_id;
            $year = $vacation->year;

            if ($vacation->isHistorical()) {
                $this->reject(
                    'vacation',
                    'El registro histórico no se puede eliminar directamente. Primero debe regularizarlo asignándole sus fechas.'
                );
            }

            $this->lockAnnualRecords($employeeId, $year);
            $vacation->delete();
            $this->syncAnnualBalance($employeeId, $year);
        }, 5);
    }

    public function annualBalance(int $employeeId, int $year, ?int $excludingId = null): int
    {
        $query = Vacation::query()->where('employee_id', $employeeId)->where('year', $year);
        if ($excludingId !== null) {
            $query->where('id', '!=', $excludingId);
        }

        return max(0, Vacation::ANNUAL_DAYS - (int) $query->sum('scheduled_days'));
    }

    private function period(array $data): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['start_date']);
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $data['end_date']);

        if ($start->year !== $end->year || $start->year !== (int) $data['year']) {
            $this->reject('end_date', 'El período debe iniciar y terminar dentro del año indicado. Divídalo en dos registros si cruza de año.');
        }

        return [$start, $end, (int) $start->diffInDays($end) + 1];
    }

    private function assertCurrentEligibility(Employee $employee, CarbonImmutable $start, CarbonImmutable $end): Contract
    {
        if ($employee->status !== 'Activo') {
            $this->reject('employee_id', 'Solo los empleados activos pueden registrar vacaciones.');
        }

        $contract = Contract::query()
            ->where('employee_id', $employee->id)
            ->whereIn('type', ['Permanente', 'Nombrado'])
            ->where('is_active', true)
            ->whereNull('cancelled_at')
            ->whereDate('start_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })
            ->whereDate('start_date', '<=', $start->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $end->toDateString());
            })
            ->lockForUpdate()
            ->first();

        if (! $contract) {
            $this->reject(
                'employee_id',
                'El empleado necesita un contrato vigente Permanente o Nombrado, sin anulación, que cubra todo el período.'
            );
        }

        return $contract;
    }

    private function assertHistoricalEligibility(Employee $employee, CarbonImmutable $start, CarbonImmutable $end): Contract
    {
        $contract = Contract::query()
            ->where('employee_id', $employee->id)
            ->whereIn('type', ['Permanente', 'Nombrado'])
            ->whereDate('start_date', '<=', $start->toDateString())
            ->where(function ($query) use ($end) {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $end->toDateString());
            })
            ->where(function ($query) use ($end) {
                $query->whereNull('cancelled_at')->orWhereDate('cancelled_at', '>=', $end->toDateString());
            })
            ->lockForUpdate()
            ->first();

        if (! $contract) {
            $this->reject(
                'employee_id',
                'No existe un contrato Permanente o Nombrado que haya estado vigente durante todo el período histórico.'
            );
        }

        return $contract;
    }

    private function assertAvailable(
        int $employeeId,
        int $year,
        CarbonImmutable $start,
        CarbonImmutable $end,
        int $days,
        ?int $excludingId = null,
    ): void {
        $annual = Vacation::query()->where('employee_id', $employeeId)->where('year', $year);
        if ($excludingId !== null) {
            $annual->where('id', '!=', $excludingId);
        }

        $alreadyScheduled = (int) (clone $annual)->sum('scheduled_days');
        if ($alreadyScheduled + $days > Vacation::ANNUAL_DAYS) {
            $this->reject(
                'end_date',
                'El período supera el máximo anual de 30 días. Saldo disponible: '.max(0, Vacation::ANNUAL_DAYS - $alreadyScheduled).' días.'
            );
        }

        $overlap = (clone $annual)
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->exists();

        if ($overlap) {
            $this->reject('start_date', 'El período se superpone con otra vacación registrada para el empleado.');
        }
    }

    private function lockAnnualRecords(int $employeeId, int $year): void
    {
        Vacation::query()
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->lockForUpdate()
            ->get(['id']);
    }

    private function syncAnnualBalance(int $employeeId, int $year): void
    {
        $balance = $this->annualBalance($employeeId, $year);
        Vacation::query()
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->update(['pending_days' => $balance]);
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
