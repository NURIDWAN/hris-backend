<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThrSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'calculation_method',
        'min_service_months',
        'prorata_formula',
        'include_allowances',
        'religious_holiday',
        'payment_days_before',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'include_allowances' => 'boolean',
            'is_active' => 'boolean',
            'min_service_months' => 'integer',
            'payment_days_before' => 'integer',
        ];
    }

    public const CALCULATION_METHODS = [
        'one_month_salary' => '1 Bulan Gaji',
        'prorata' => 'Prorata',
    ];

    public const RELIGIOUS_HOLIDAYS = [
        'idul_fitri' => 'Idul Fitri',
        'christmas' => 'Natal',
        'nyepi' => 'Nyepi',
        'waisak' => 'Waisak',
        'imlek' => 'Imlek',
    ];

    public const PRORATA_FORMULAS = [
        'months_worked_per_12' => 'Bulan Kerja / 12',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getCalculationMethodLabelAttribute(): string
    {
        return self::CALCULATION_METHODS[$this->calculation_method] ?? $this->calculation_method;
    }

    public function getReligiousHolidayLabelAttribute(): string
    {
        return self::RELIGIOUS_HOLIDAYS[$this->religious_holiday] ?? $this->religious_holiday;
    }

    public function getProrataFormulaLabelAttribute(): string
    {
        return self::PRORATA_FORMULAS[$this->prorata_formula] ?? $this->prorata_formula;
    }
}
