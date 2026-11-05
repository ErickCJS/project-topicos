<?php

namespace App\Models\admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ProgrammingChange extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
