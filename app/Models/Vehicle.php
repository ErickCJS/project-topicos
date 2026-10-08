<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'code', 'name', 'plate', 'year', 'load_capacity', 'occupats_capacity',
        'fuel_capacity', 'compact_capacity', 'type_id', 'brand_id', 'model_id', 'color_id',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function model()
    {
        return $this->belongsTo(Brandmodel::class, 'model_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function type()
    {
        return $this->belongsTo(Vehicletype::class, 'type_id');
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }
}
