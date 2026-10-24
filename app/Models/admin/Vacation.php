<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacation extends Model
{
    public const ANNUAL_DAYS = 30;

    public const STATUSES = ['Pendiente', 'Aprobada', 'Rechazada'];

    protected $fillable = [
        'employee_id', 'year', 'start_date', 'end_date', 'scheduled_days', 'pending_days',
        'status', 'rejection_reason', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'scheduled_days' => 'integer',
            'pending_days' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeCovering(Builder $query, string $date): Builder
    {
        return $query->where('status', 'Aprobada')
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public static function employeeIsOnVacation(int $employeeId, string $date): bool
    {
        return static::query()->where('employee_id', $employeeId)
            ->covering($date)
            ->exists();
    }

    public function isHistorical(): bool
    {
        return $this->start_date === null || $this->end_date === null;
    }

    public function statusLabel(): string
    {
        return $this->status ?: 'Pendiente';
    }

    public function isPending(): bool
    {
        return $this->status === 'Pendiente';
    }

    public function isApproved(): bool
    {
        return $this->status === 'Aprobada';
    }

    public function isRejected(): bool
    {
        return $this->status === 'Rechazada';
    }
}
