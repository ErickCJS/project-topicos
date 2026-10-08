<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicletype extends Model
{
    protected $table = 'vehicletypes';
    protected $fillable = ['name', 'description'];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'type_id');
    }
}
