<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;

class StaffGroupDay extends Model
{
    protected $fillable = [
        'staff_group_id',
        'day',
    ];
}
