<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CitizenTrackingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'programming_id' => $this['programming_id'],
            'vehicle' => $this['vehicle'],
            'location' => $this['location'],
            'last_seen_at' => $this['last_seen_at'],
            'age_seconds' => $this['age_seconds'],
            'is_stale' => $this['is_stale'],
            'is_low_accuracy' => $this['is_low_accuracy'],
            'route_started' => $this['route_started'],
        ];
    }
}
