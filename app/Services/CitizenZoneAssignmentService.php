<?php

namespace App\Services;

use App\Exceptions\CitizenZoneConflictException;
use App\Models\Citizen;
use App\Models\CitizenZoneAssignment;
use App\Models\admin\Zone;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CitizenZoneAssignmentService
{
    public function __construct(private CitizenZoneDetectionService $detection) {}

    public function confirm(Citizen $citizen, float $latitude, float $longitude, float $accuracy): array
    {
        $zones = $this->detection->detect($latitude, $longitude, $accuracy);

        if ($zones->count() !== 1) {
            return [
                'status' => $zones->isEmpty() ? 404 : 409,
                'zone' => null,
                'changed' => false,
            ];
        }

        /** @var Zone $zone */
        $zone = $zones->first();

        return DB::transaction(function () use ($citizen, $zone, $latitude, $longitude, $accuracy): array {
            $lockedCitizen = Citizen::query()->whereKey($citizen->id)->lockForUpdate()->firstOrFail();

            $activeAssignments = CitizenZoneAssignment::query()
                ->where('citizen_id', $lockedCitizen->id)
                ->whereNull('ended_at')
                ->latest('assigned_at')
                ->latest('id')
                ->lockForUpdate()
                ->get();

            $active = $this->reconcileActiveAssignments($lockedCitizen, $activeAssignments);

            if ($active && (int) $lockedCitizen->zone_id === (int) $zone->id) {
                return ['status' => 200, 'zone' => $zone, 'changed' => false];
            }

            if ($active) {
                $active->update(['ended_at' => now()]);
            }

            $assignment = CitizenZoneAssignment::create([
                'citizen_id' => $lockedCitizen->id,
                'zone_id' => $zone->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy' => $accuracy,
                'assigned_at' => now(),
            ]);

            $lockedCitizen->update(['zone_id' => $zone->id]);

            return ['status' => 200, 'zone' => $zone, 'changed' => true, 'assignment' => $assignment];
        });
    }

    private function reconcileActiveAssignments(Citizen $citizen, Collection $activeAssignments): ?CitizenZoneAssignment
    {
        if ($activeAssignments->isEmpty()) {
            return null;
        }

        $citizenZoneId = $citizen->zone_id === null ? null : (int) $citizen->zone_id;
        $matching = $citizenZoneId === null
            ? $activeAssignments
            : $activeAssignments->filter(fn (CitizenZoneAssignment $assignment) => (int) $assignment->zone_id === $citizenZoneId);

        if ($citizenZoneId !== null && $matching->isEmpty()) {
            throw new CitizenZoneConflictException('La zona actual del ciudadano no coincide con sus asignaciones activas.');
        }

        if ($citizenZoneId === null && $activeAssignments->pluck('zone_id')->unique()->count() > 1) {
            throw new CitizenZoneConflictException('Existen asignaciones activas para zonas diferentes y no se puede determinar la zona válida.');
        }

        /** @var CitizenZoneAssignment $canonical */
        $canonical = ($matching->isNotEmpty() ? $matching : $activeAssignments)->first();

        foreach ($activeAssignments as $assignment) {
            if ((int) $assignment->id !== (int) $canonical->id) {
                $assignment->update(['ended_at' => now()]);
            }
        }

        if ($citizenZoneId === null) {
            $citizen->update(['zone_id' => $canonical->zone_id]);
        }

        return $canonical;
    }
}
