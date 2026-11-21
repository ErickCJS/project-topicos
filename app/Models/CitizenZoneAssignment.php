<?php

namespace App\Models;

use App\Models\admin\Zone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenZoneAssignment extends Model
{
    protected $fillable = [
        'citizen_id',
        'zone_id',
        'latitude',
        'longitude',
        'accuracy',
        'assigned_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}
