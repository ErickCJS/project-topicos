<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ContractRules
{
    public const MAX_TEMPORAL_MONTHS = 3;

    /** Validate the complete history, including contracts after an edited record. */
    public function validate(array $data, iterable $history, bool $creating, CarbonImmutable $today): void
    {
        if ($data['type'] === 'Temporal') {
            $maximum = CarbonImmutable::parse($data['start_date'])->startOfDay()
                ->addMonthsNoOverflow(self::MAX_TEMPORAL_MONTHS);
            if (CarbonImmutable::parse($data['end_date'])->startOfDay()->gt($maximum)) {
                $this->reject('end_date', 'El contrato temporal no puede durar más de tres meses. Fecha máxima de término: '.$maximum->format('d/m/Y').'.');
            }
        }

        $previous = collect($history)->map(fn ($contract) => [
            'type' => $contract->type,
            'start_date' => CarbonImmutable::parse($contract->start_date)->startOfDay(),
            'end_date' => $contract->end_date ? CarbonImmutable::parse($contract->end_date)->startOfDay() : null,
        ]);

        if ($creating && $previous->isNotEmpty()) {
            if ($previous->contains(fn ($contract) => $contract['type'] !== 'Temporal' || !$contract['end_date'])) {
                $this->reject('employee_id', 'Este trabajador tiene un contrato Permanente o Nombrado y no puede tener otro contrato.');
            }

            $lastEnd = $previous->max('end_date');
            $available = $lastEnd->addMonthsNoOverflow(2);
            if ($today->startOfDay()->lt($available)) {
                $this->reject('employee_id', 'Debe esperar hasta el '.$available->format('d/m/Y').' para registrar un nuevo contrato.');
            }
            if (CarbonImmutable::parse($data['start_date'])->startOfDay()->lt($available)) {
                $this->reject('start_date', 'El nuevo contrato no puede iniciar antes del '.$available->format('d/m/Y').'.');
            }
        }

        $contracts = $previous->push([
            'type' => $data['type'],
            'start_date' => CarbonImmutable::parse($data['start_date'])->startOfDay(),
            'end_date' => !empty($data['end_date']) ? CarbonImmutable::parse($data['end_date'])->startOfDay() : null,
        ])->sortBy('start_date')->values();

        for ($i = 1; $i < $contracts->count(); $i++) {
            $before = $contracts[$i - 1];
            if ($before['type'] !== 'Temporal' || !$before['end_date']) {
                $this->reject('type', 'Un contrato Permanente o Nombrado no permite contratos posteriores.');
            }
            $available = $before['end_date']->addMonthsNoOverflow(2);
            if ($contracts[$i]['start_date']->lt($available)) {
                $this->reject('start_date', 'Entre el término de un contrato temporal y el inicio del siguiente deben pasar al menos dos meses. Fecha mínima: '.$available->format('d/m/Y').'.');
            }
        }
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
