<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;

class Reason extends Model
{
    protected $table = 'reason';

    protected $fillable = [
        'name',
        'description',
    ];
}
