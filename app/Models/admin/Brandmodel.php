<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use App\Models\admin\Brand;
use App\Models\admin\Vehicle;

class Brandmodel extends Model
{
    protected $fillable = ['name', 'code', 'description', 'brand_id'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'model_id');
    }
}
