<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shifts extends Model
{
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
