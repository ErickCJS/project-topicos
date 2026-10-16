<?php

namespace App\Models\admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
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

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function hasVigenteContract(): bool
    {
        return $this->contracts()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', today())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', today());
            })
            ->exists();
    }
}
