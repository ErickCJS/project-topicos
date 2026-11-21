<?php

namespace App\Services;

use App\Exceptions\CitizenZoneConflictException;
use App\Models\Citizen;
use App\Models\admin\Programming;
use App\Models\admin\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CitizenProgrammingService
{
    public function paginate(Citizen $citizen, array $filters): LengthAwarePaginator
    {
        $zone = $this->confirmedZone($citizen);

        if (! $zone) {
            throw new CitizenZoneConflictException('No tienes una zona confirmada para consultar programaciones.');
        }

        $query = Programming::query()
            ->with([
                'shift:id,name,start_time,end_time',
                'vehicle:id,name,code,plate',
            ])
            ->where('zone_id', $zone->id)
            ->where('status', '!=', Programming::STATUS_CANCELLED);

        $this->applyDateFilters($query, $filters);

        $programming = $query
            ->orderBy('date')
            ->orderBy('shift_id')
            ->orderBy('id')
            ->paginate((int) ($filters['per_page'] ?? 15));

        $programming->getCollection()->each(
            fn (Programming $item) => $item->setRelation('zone', $zone)
        );

        return $programming;
    }

    public function find(Citizen $citizen, int $id): Programming
    {
        $zone = $this->confirmedZone($citizen);

        if (! $zone) {
            throw new CitizenZoneConflictException('No tienes una zona confirmada para consultar programaciones.');
        }

        $programming = Programming::query()
            ->with([
                'shift:id,name,start_time,end_time',
                'vehicle:id,name,code,plate',
            ])
            ->whereKey($id)
            ->where('zone_id', $zone->id)
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->firstOrFail();

        return $programming->setRelation('zone', $zone);
    }

    private function confirmedZone(Citizen $citizen): ?Zone
    {
        $assignment = $citizen->currentZoneAssignment()->with('zone')->first();
        $citizenZoneId = $citizen->zone_id === null ? null : (int) $citizen->zone_id;
        $assignmentZoneId = $assignment?->zone_id === null ? null : (int) $assignment->zone_id;

        if ($citizenZoneId !== null && $assignmentZoneId !== null && $citizenZoneId !== $assignmentZoneId) {
            throw new CitizenZoneConflictException('La zona confirmada del ciudadano es inconsistente.');
        }

        return $assignment?->zone ?? $citizen->zone()->first();
    }

    private function applyDateFilters($query, array $filters): void
    {
        if (! empty($filters['month'])) {
            $start = $filters['month'].'-01';
            $end = date('Y-m-t', strtotime($start));
            $query->whereBetween('date', [$start, $end]);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('date', '<=', $filters['to']);
        }
    }
}
