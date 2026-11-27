<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffGroupHelper extends Model
{
    protected $table = 'staff_group_helpers';

    protected $fillable = [
        'staff_group_id',
        'employee_id',
    ];


    public function staffGroup(): BelongsTo
    {
        return $this->belongsTo(StaffGroup::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
