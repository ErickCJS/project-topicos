<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = [
        'name',
        'date',
        'description',
        'state'
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'state' => 'boolean'
        ];
    }
}
