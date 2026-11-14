<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permission extends Model
{
    protected $fillable = ['module_id', 'name', 'slug', 'description'];
    public function module(): BelongsTo { return $this->belongsTo(Module::class); }
    public function routes(): HasMany { return $this->hasMany(PermissionRoute::class); }
    public function userTypes(): BelongsToMany { return $this->belongsToMany(UserType::class, 'user_type_permissions'); }
}
