<?php

namespace App\\Models\\admin;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;

class ProgrammingLocation extends Model
{
    protected $fillable = [
        'programming_id',
        'employee_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_meters' => 'decimal:2',
            'recorded_at' => 'datetime',
        ];
    }

    public function programming(): BelongsTo
    {
        return $this->belongsTo(Programming::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
