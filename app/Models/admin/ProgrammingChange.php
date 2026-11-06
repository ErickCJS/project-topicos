<?php

namespace App\Models\admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProgrammingChange extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array', 'resources' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function programming()
    {
        return $this->belongsTo(Programming::class);
    }

    public function originProgramming()
    {
        return $this->belongsTo(Programming::class, 'origin_programming_id');
    }

    public function destinationProgramming()
    {
        return $this->belongsTo(Programming::class, 'destination_programming_id');
    }
}
