<?php

namespace App\Services;

use App\Exceptions\CitizenZoneConflictException;
use App\Models\Citizen;
use App\Models\admin\Programming;
use App\Models\admin\ProgrammingLocation;
use App\Models\admin\Zone;
use Carbon\CarbonImmutable;

class CitizenTrackingService
{
    public function locate(Citizen $citizen, int $programmingId): array
    {
        $zone = $this->confirmedZone($citizen);

        if (! $zone) {
            throw new CitizenZoneConflictException('No tienes una zona confirmada para consultar el seguimiento.');
        }

        $programming = Programming::query()
            ->with('vehicle:id,name,plate')
            ->whereKey($programmingId)
            ->where('zone_id', $zone->id)
            ->first();

        if (! $programming) {
            return ['status' => 404];
        }

        if ($programming->status === Programming::STATUS_CANCELLED) {
            return ['status' => 404];
        }

        if ($programming->finished_at !== null || $programming->status === Programming::STATUS_FINISHED) {
            return ['status' => 409];
        }

        if (
            $programming->status !== Programming::STATUS_STARTED
            || $programming->started_at === null
            || $programming->finished_at !== null
        ) {
            return ['status' => 409];
        }

        $location = ProgrammingLocation::query()
            ->where('programming_id', $programming->id)
            ->where('employee_id', $programming->driver_id)
            ->latest('id')
            ->first();

        $data = [
            'programming_id' => $programming->id,
            'vehicle' => [
                'id' => $programming->vehicle?->id,
                'name' => $programming->vehicle?->name,
                'plate' => $programming->vehicle?->plate,
            ],
            'location' => null,
            'last_seen_at' => null,
            'age_seconds' => null,
            'is_stale' => null,
            'is_low_accuracy' => null,
            'route_started' => true,
        ];

        if (! $location || ! $this->validCoordinates($location)) {
            return ['status' => 200, 'data' => $data];
        }

        $recordedAt = CarbonImmutable::parse((string) $location->getRawOriginal('recorded_at'), 'UTC');
        $now = CarbonImmutable::now('UTC');
        $ageSeconds = max(0, $recordedAt->diffInSeconds($now, false));
        $accuracy = $location->accuracy;

        $data['location'] = [
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'accuracy' => $accuracy === null ? null : (float) $accuracy,
            'recorded_at' => $recordedAt->toIso8601String(),
        ];
        $data['last_seen_at'] = $recordedAt->toIso8601String();
        $data['age_seconds'] = $ageSeconds;
        $data['is_stale'] = $ageSeconds > (int) config('citizen.tracking.stale_seconds', 120);
        $data['is_low_accuracy'] = $accuracy === null
            ? null
            : (float) $accuracy > (float) config('citizen.tracking.low_accuracy_meters', 50);

        return ['status' => 200, 'data' => $data];
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

    private function validCoordinates(ProgrammingLocation $location): bool
    {
        return is_numeric($location->latitude)
            && is_numeric($location->longitude)
            && (float) $location->latitude >= -90
            && (float) $location->latitude <= 90
            && (float) $location->longitude >= -180
            && (float) $location->longitude <= 180
            && $location->getRawOriginal('recorded_at') !== null;
    }
}
