<?php

namespace App\Services;

use App\Exceptions\CitizenHistoryAccessException;
use App\Models\Citizen;
use App\Models\admin\Programming;
use App\Models\admin\Zone;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CitizenHistoryService
{
    public function paginate(Citizen $citizen, array $filters): LengthAwarePaginator
    {
        $this->assertZoneFilter($citizen, $filters['zone_id'] ?? null);
        $this->recordInconsistencies($citizen, $filters);

        $query = $this->baseQuery($citizen, $filters);
        $this->applyDateFilters($query, $filters);

        $history = $query
            ->orderByDesc('date')
            ->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 15));

        $this->attachZones($history->getCollection());

        return $history;
    }

    public function find(Citizen $citizen, int $id): Programming
    {
        $this->recordInconsistencies($citizen, [], $id);
        $programming = $this->baseQuery($citizen, [])->whereKey($id)->first();

        if (! $programming) {
            abort(404);
        }

        $this->attachZones(collect([$programming]));

        return $programming;
    }

    private function baseQuery(Citizen $citizen, array $filters): Builder
    {
        $query = Programming::query()
            ->with([
                'shift:id,name,start_time,end_time',
                'vehicle:id,name,code,plate',
            ])
            ->whereDate('date', '<=', today()->toDateString())
            ->where('status', Programming::STATUS_FINISHED)
            ->whereNotNull('finished_at')
            ->where('status', '!=', Programming::STATUS_CANCELLED)
            ->where('status', '!=', Programming::STATUS_RESCHEDULED)
            ->whereNotExists(function ($childQuery): void {
                $childQuery->select(DB::raw(1))
                    ->from('programmings as descendants')
                    ->whereColumn('descendants.parent_programming_id', 'programmings.id')
                    ->whereNull('descendants.deleted_at');
            });

        if (array_key_exists('zone_id', $filters) && $filters['zone_id'] !== null) {
            $query->where('zone_id', (int) $filters['zone_id']);
        }

        $query->whereRaw(
            '(SELECT COUNT(*) FROM citizen_zone_assignments AS historical_assignments
              WHERE historical_assignments.citizen_id = ?
                AND historical_assignments.zone_id = programmings.zone_id
                AND DATE(historical_assignments.assigned_at) <= DATE(programmings.date)
                AND (historical_assignments.ended_at IS NULL
                     OR DATE(historical_assignments.ended_at) >= DATE(programmings.date))) = 1',
            [$citizen->id],
        );

        return $query;
    }

    private function applyDateFilters(Builder $query, array $filters): void
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

    private function assertZoneFilter(Citizen $citizen, mixed $zoneId): void
    {
        if ($zoneId === null) {
            return;
        }

        $authorized = $citizen->zoneAssignments()
            ->where('zone_id', (int) $zoneId)
            ->exists();

        if (! $authorized) {
            throw new CitizenHistoryAccessException('La zona solicitada no pertenece al historial del ciudadano.');
        }
    }

    private function attachZones($programmings): void
    {
        $zoneIds = $programmings->pluck('zone_id')->filter()->unique()->values();
        $zones = Zone::query()->whereIn('id', $zoneIds)->get()->keyBy('id');

        $programmings->each(function (Programming $programming) use ($zones): void {
            $programming->setRelation('zone', $zones->get($programming->zone_id));
        });
    }

    private function recordInconsistencies(Citizen $citizen, array $filters, ?int $programmingId = null): void
    {
        $query = Programming::query()
            ->whereDate('date', '<=', today()->toDateString())
            ->where('status', Programming::STATUS_FINISHED)
            ->whereNotNull('finished_at')
            ->whereNotExists(function ($childQuery): void {
                $childQuery->select(DB::raw(1))
                    ->from('programmings as descendants')
                    ->whereColumn('descendants.parent_programming_id', 'programmings.id')
                    ->whereNull('descendants.deleted_at');
            })
            ->whereRaw(
                '(SELECT COUNT(*) FROM citizen_zone_assignments AS historical_assignments
                  WHERE historical_assignments.citizen_id = ?
                    AND historical_assignments.zone_id = programmings.zone_id
                    AND DATE(historical_assignments.assigned_at) <= DATE(programmings.date)
                    AND (historical_assignments.ended_at IS NULL
                         OR DATE(historical_assignments.ended_at) >= DATE(programmings.date))) > 1',
                [$citizen->id],
            );

        if ($programmingId !== null) {
            $query->whereKey($programmingId);
        }

        if (array_key_exists('zone_id', $filters) && $filters['zone_id'] !== null) {
            $query->where('zone_id', (int) $filters['zone_id']);
        }

        $this->applyDateFilters($query, $filters);
        $count = $query->count();

        if ($count > 0) {
            Log::notice('Citizen history excluded inconsistent zone assignments.', [
                'citizen_id' => $citizen->id,
                'programming_count' => $count,
                'programming_id' => $programmingId,
            ]);
        }
    }
}
