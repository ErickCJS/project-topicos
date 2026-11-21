<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    public const TYPE_PREVENTIVE = 'Preventivo';
    public const TYPE_CLEANING = 'Limpieza';
    public const TYPE_REPAIR = 'Reparación';

    protected $fillable = [
        'maintenance_id',
        'vehicle_id',
        'responsible_id',
        'type',
        'weekday',
        'start_time',
        'end_time',
    ];

    public function maintenance(): BelongsTo
    {
        return $this->belongsTo(Maintenance::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'responsible_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(MaintenanceDetail::class);
    }
}
