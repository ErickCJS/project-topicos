<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacation extends Model
{
    public const ANNUAL_DAYS = 30;

    protected $fillable = [
        'employee_id', 'year', 'start_date', 'end_date', 'scheduled_days', 'pending_days',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'scheduled_days' => 'integer',
            'pending_days' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeCovering(Builder $query, string $date): Builder
    {
        return $query->whereNotNull('start_date')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);
    }

    public static function employeeIsOnVacation(int $employeeId, string $date): bool
    {
        return static::query()->where('employee_id', $employeeId)->covering($date)->exists();
    }

    public function isHistorical(): bool
    {
        return $this->start_date === null || $this->end_date === null;
    }

    public function statusLabel(): string
    {
        if ($this->isHistorical()) {
            return 'Pendiente de regularización';
        }

        if ($this->start_date->gt(today())) {
            return 'Programada';
        }

        if ($this->end_date->lt(today())) {
            return 'Finalizada';
        }

        return 'En vacaciones';
    }
}
