<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermissionRoute extends Model
{
    protected $fillable = ['permission_id', 'route_name'];
    public function permission(): BelongsTo { return $this->belongsTo(Permission::class); }
}
