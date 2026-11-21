<?php

namespace App\Models;

use App\Models\admin\Programming;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitizenRating extends Model
{
    protected $fillable = [
        'programming_id',
        'stars',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'stars' => 'integer',
        ];
    }

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class);
    }

    public function programming(): BelongsTo
    {
        return $this->belongsTo(Programming::class);
    }
}
