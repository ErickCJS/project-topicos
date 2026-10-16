<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    public const TYPES = ['Entrada', 'Salida'];

    public const STATUSES = ['Presente', 'Ausente'];

    protected $fillable = [
        'employee_id',
        'shift_id',
        'date',
        'time',
        'type',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shifts::class);
    }
}
