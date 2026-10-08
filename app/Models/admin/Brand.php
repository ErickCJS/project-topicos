<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\admin\Brandmodel;
use App\Models\admin\Vehicle;

class Brand extends Model
{
    protected $fillable = ['name', 'description', 'logo'];

    public function models()
    {
        return $this->hasMany(Brandmodel::class, 'brand_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
