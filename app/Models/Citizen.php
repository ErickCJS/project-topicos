<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use App\Models\admin\Zone;

class Citizen extends Model implements AuthenticatableContract
{
    use HasApiTokens, Authenticatable;

    protected $fillable = [
        'dni',
        'first_name',
        'last_name',
        'email',
        'password',
        'zone_id',
    ];

    protected $hidden = [
        'password',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function zoneAssignments(): HasMany
    {
        return $this->hasMany(CitizenZoneAssignment::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(CitizenRating::class);
    }

    public function currentZoneAssignment(): HasOne
    {
        return $this->hasOne(CitizenZoneAssignment::class)
            ->whereNull('ended_at')
            ->latestOfMany('assigned_at');
    }
}
