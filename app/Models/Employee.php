<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'nid',
        'designation',
        'joining_date',
        'base_salary',
        'current_advance_due',
        'address',
        'emergency_contact',
        'bank_or_mfs_details',
        'is_active',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'base_salary' => 'decimal:2',
        'current_advance_due' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function advances(): HasMany
    {
        return $this->hasMany(SalaryAdvance::class)->orderBy('advance_date', 'desc');
    }

    public function salaries(): HasMany
    {
        return $this->hasMany(Salary::class)->orderBy('salary_month', 'desc');
    }

    public function getTotalPaidSalariesAttribute(): float
    {
        return (float) $this->salaries()->sum('paid_amount');
    }
}
