<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffGroup extends Model
{
    protected $fillable = ['name','shift_id','zone_id','vehicle_id','driver_id', 'state'];

    public function days(): HasMany
    {
        return $this->hasMany(StaffGroupDay::class);
    }

    public function helpers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'staff_group_helpers');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'driver_id');
    }

}
