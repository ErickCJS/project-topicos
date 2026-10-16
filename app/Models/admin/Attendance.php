<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;

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
}
