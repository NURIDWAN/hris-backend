<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'default_days',
        'is_paid',
        'requires_approval',
        'requires_attachment',
        'max_consecutive_days',
        'min_notice_days',
        'is_carry_forward',
        'max_carry_forward_days',
        'is_active',
        'color',
        'is_annual',
    ];

    protected function casts(): array
    {
        return [
            'default_days' => 'integer',
            'max_consecutive_days' => 'integer',
            'min_notice_days' => 'integer',
            'max_carry_forward_days' => 'integer',
            'is_paid' => 'boolean',
            'requires_approval' => 'boolean',
            'requires_attachment' => 'boolean',
            'is_carry_forward' => 'boolean',
            'is_active' => 'boolean',
            'is_annual' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    public function getPaidLabelAttribute(): string
    {
        return $this->is_paid ? 'Berbayar' : 'Tidak Berbayar';
    }

    public function getColorClassAttribute(): string
    {
        return $this->color ?? 'primary';
    }
}
