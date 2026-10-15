<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    public const TYPES = ['Permanente', 'Nombrado', 'Temporal'];

    protected $fillable = ['employee_id', 'type', 'start_date', 'end_date', 'salary', 'is_active'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'salary' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function allowsVacations(): bool
    {
        return in_array($this->type, ['Permanente', 'Nombrado'], true);
    }
}
