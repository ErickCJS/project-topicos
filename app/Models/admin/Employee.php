<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $hidden = ['password'];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }
}
