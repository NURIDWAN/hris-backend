<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'code',
        'description',
        'level',
        'base_salary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'base_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function getFormattedSalaryAttribute(): string
    {
        return 'Rp '.number_format($this->base_salary, 0, ',', '.');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
