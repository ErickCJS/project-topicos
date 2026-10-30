<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'description',
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}