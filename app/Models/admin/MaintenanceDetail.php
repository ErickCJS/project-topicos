<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceDetail extends Model
{
    public const STATUS_PENDING = 'Pendiente';
    public const STATUS_COMPLETED = 'Realizado';
    public const STATUS_NOT_COMPLETED = 'No realizado';

    protected $fillable = [
        'maintenance_schedule_id',
        'scheduled_date',
        'observation',
        'image_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
        ];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }
}
