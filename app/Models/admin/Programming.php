<?php

namespace App\Models\admin;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Programming extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date', 'snapshot' => 'array', 'finished_at' => 'datetime'];
    }

    public function setDateAttribute($value): void
    {
        // A route date is a calendar day, not a timestamp (also on SQLite).
        $this->attributes['date'] = CarbonImmutable::parse($value)->toDateString();
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'programming_employees')->withPivot('role');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function changes()
    {
        return $this->hasMany(ProgrammingChange::class);
    }
}
