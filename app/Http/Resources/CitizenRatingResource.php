<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenRatingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $programming = $this->programming;
        $shift = $programming?->shift;
        $vehicle = $programming?->vehicle;
        $zone = $programming?->relationLoaded('zone') ? $programming->getRelation('zone') : null;
        $snapshot = is_array($programming?->snapshot) ? $programming->snapshot : [];
        $hours = $this->validHours($snapshot['hours'] ?? null);

        return [
            'id' => $this->id,
            'programming' => [
                'id' => $programming?->id,
                'date' => $programming?->date?->toDateString(),
                'zone' => ['id' => $zone?->id, 'name' => $zone?->name],
                'shift' => [
                    'id' => $shift?->id,
                    'name' => $this->validText($snapshot['shift'] ?? null) ?? $shift?->name,
                    'schedule' => [
                        'start' => $hours[0] ?? substr((string) ($shift?->start_time ?? ''), 0, 5),
                        'end' => $hours[1] ?? substr((string) ($shift?->end_time ?? ''), 0, 5),
                    ],
                ],
                'vehicle' => [
                    'id' => $vehicle?->id,
                    'name' => $this->validText($snapshot['vehicle'] ?? null) ?? $vehicle?->name,
                    'plate' => $this->validText($snapshot['plate'] ?? null) ?? $vehicle?->plate,
                ],
            ],
            'stars' => $this->stars,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function validText(mixed $value): ?string
    {
        if (! is_string($value)) return null;
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    /** @return array{0:string,1:string}|null */
    private function validHours(mixed $value): ?array
    {
        if (! is_string($value) || ! preg_match('/^((?:[01]\d|2[0-3]):[0-5]\d)\s*-\s*((?:[01]\d|2[0-3]):[0-5]\d)$/', trim($value), $matches)) return null;
        return [$matches[1], $matches[2]];
    }
}
