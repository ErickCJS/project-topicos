<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\admin\Vehicle;

class Color extends Model
{
    protected $table = 'color';
    protected $fillable = ['name', 'code', 'description'];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
