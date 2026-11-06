<?php

namespace App\Models\admin;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\admin\Vehicle;

class Programming extends Model
{
    use SoftDeletes;

    public const STATUS_PROGRAMMED = 'Programado';
    public const STATUS_RESCHEDULED = 'Reprogramado';
    public const STATUS_FINISHED = 'Finalizado';
    public const STATUS_CANCELLED = 'Anulado';

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
        return $this->belongsToMany(Employee::class, 'programming_employees')->withPivot('role', 'date', 'shift_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function changes()
    {
        return $this->hasMany(ProgrammingChange::class);
    }

    public function parentProgramming(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_programming_id');
    }

    public function childProgrammings()
    {
        return $this->hasMany(self::class, 'parent_programming_id');
    }

    public function relatedProgrammings()
    {
        return self::query()->whereKey($this->id)
            ->orWhere('parent_programming_id', $this->id)
            ->orWhereIn('id', function ($query) {
                $query->select('parent_programming_id')->from('programmings')->where('id', $this->id);
            });
    }

    public function hasChildProgramming(): bool
    {
        return $this->childProgrammings()->exists();
    }
}
