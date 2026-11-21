<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenProgrammingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $shift = $this->whenLoaded('shift');
        $vehicle = $this->whenLoaded('vehicle');
        $zone = $this->whenLoaded('zone');
        $snapshot = is_array($this->snapshot) ? $this->snapshot : [];
        $snapshotHours = $this->validSnapshotHours($snapshot['hours'] ?? null);

        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'zone' => [
                'id' => $zone->id,
                'name' => $zone->name,
            ],
            'shift' => [
                'id' => $shift->id,
                // The snapshot preserves the historical public label. The relation is the fallback for old/incomplete rows.
                'name' => $this->validSnapshotText($snapshot['shift'] ?? null) ?? $shift->name,
                'schedule' => [
                    'start' => $snapshotHours[0] ?? substr((string) $shift->start_time, 0, 5),
                    'end' => $snapshotHours[1] ?? substr((string) $shift->end_time, 0, 5),
                ],
            ],
            'status' => $this->status,
            'vehicle' => [
                'id' => $vehicle->id,
                // The snapshot has no vehicle code; therefore code always uses the current relation.
                'name' => $this->validSnapshotText($snapshot['vehicle'] ?? null) ?? $vehicle->name,
                'code' => $vehicle->code,
                'plate' => $this->validSnapshotText($snapshot['plate'] ?? null) ?? $vehicle->plate,
            ],
        ];
    }

    private function validSnapshotText(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @return array{0:string,1:string}|null */
    private function validSnapshotHours(mixed $value): ?array
    {
        if (! is_string($value)
            || ! preg_match('/^((?:[01]\\d|2[0-3]):[0-5]\\d)\\s*-\\s*((?:[01]\\d|2[0-3]):[0-5]\\d)$/', trim($value), $matches)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }
}
