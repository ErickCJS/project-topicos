<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\admin\Vehicle;

class VehicleImage extends Model
{
    protected $fillable = ['imagen', 'profile', 'vehicle_id'];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
