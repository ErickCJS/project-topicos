<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\admin\Vehicle;

class Vehicletype extends Model
{
    protected $table = 'vehicletypes';
    protected $fillable = ['name', 'description'];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'type_id');
    }
}
