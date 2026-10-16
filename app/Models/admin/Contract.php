<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Contract extends Model
{
    public const TYPES = ['Permanente', 'Nombrado', 'Temporal'];

    protected $attributes = ['is_active' => true];

    protected $fillable = ['employee_id', 'type', 'start_date', 'end_date', 'salary', 'is_active'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'salary' => 'decimal:2', 'is_active' => 'boolean', 'cancelled_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowsVacations(): bool
    {
        return !$this->isClosed() && in_array($this->type, ['Permanente', 'Nombrado'], true);
    }

    public function effectiveEndDate(): ?Carbon
    {
        return $this->cancelled_at?->copy()->startOfDay() ?? $this->end_date;
    }

    public function isClosed(): bool
    {
        return $this->cancelled_at !== null || !$this->is_active || ($this->end_date && $this->end_date->lt(today()));
    }

    public function statusLabel(): string
    {
        if ($this->cancelled_at || !$this->is_active) {
            return 'Anulado';
        }
        if ($this->end_date && $this->end_date->lt(today())) {
            return 'Finalizado';
        }

        return $this->start_date->gt(today()) ? 'Por iniciar' : 'Vigente';
    }
}
