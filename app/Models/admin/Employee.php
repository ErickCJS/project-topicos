<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model implements AuthenticatableContract
{
    use Authenticatable;
    protected $hidden = ['password'];

    protected $fillable = [
        'employee_type_id', 'dni', 'first_name', 'last_name', 'birthdate',
        'license', 'phone', 'address', 'email', 'password', 'status', 'profile_photo_path',
    ];

    public function employeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class, 'employee_type_id');
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function hasVigenteContract(): bool
    {
        return self::query()
            ->withVigenteContract()
            ->whereKey($this->getKey())
            ->exists();
    }

    public function scopeWithVigenteContract(Builder $query): Builder
    {
        return $query->whereHas('contracts', function (Builder $contractQuery) {
            $contractQuery->where('is_active', true)
                ->whereNull('cancelled_at')
                ->whereDate('start_date', '<=', today())
                ->where(function (Builder $dateQuery) {
                    $dateQuery->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', today());
                });
        });
    }
}
