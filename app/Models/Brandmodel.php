<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
