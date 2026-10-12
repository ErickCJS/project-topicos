<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;

class EmployeeType extends Model
{
    protected $table = 'employee_types';

    protected $fillable = ['name', 'description'];
}