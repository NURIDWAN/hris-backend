<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'date',
        'type',
        'description',
        'is_recurring',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeForMonth($query, int $month)
    {
        return $query->whereMonth('date', $month);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->startOfDay())
            ->orderBy('date');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'national' => 'Libur Nasional',
            'company' => 'Libur Perusahaan',
            'religious' => 'Libur Keagamaan',
            default => '-',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'national' => 'danger',
            'company' => 'primary',
            'religious' => 'warning',
            default => 'secondary',
        };
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date->translatedFormat('l, d F Y');
    }

    public function getShortDateAttribute(): string
    {
        return $this->date->format('d M Y');
    }

    // Helpers
    public static function isHoliday(int $companyId, $date): bool
    {
        return static::forCompany($companyId)
            ->active()
            ->whereDate('date', $date)
            ->exists();
    }

    public static function getHolidaysForDateRange(int $companyId, $startDate, $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return static::forCompany($companyId)
            ->active()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    public static function countHolidaysInRange(int $companyId, $startDate, $endDate): int
    {
        return static::forCompany($companyId)
            ->active()
            ->whereBetween('date', [$startDate, $endDate])
            ->count();
    }
}
