<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_admin', 'is_system', 'is_active'];
    protected function casts(): array { return ['is_admin' => 'boolean', 'is_system' => 'boolean', 'is_active' => 'boolean']; }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function permissions(): BelongsToMany { return $this->belongsToMany(Permission::class, 'user_type_permissions'); }
}
