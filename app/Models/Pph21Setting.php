<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pph21Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'is_gross_up',
        'use_ter',
        'npwp_discount_rate',
        'effective_year',
        'notes',
    ];

    protected $casts = [
        'is_gross_up' => 'boolean',
        'use_ter' => 'boolean',
        'npwp_discount_rate' => 'decimal:2',
        'effective_year' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getCalculationMethodLabelAttribute(): string
    {
        return $this->use_ter ? 'TER (Tarif Efektif Rata-rata)' : 'Tarif Progresif';
    }

    public function getTaxBurdenLabelAttribute(): string
    {
        return $this->is_gross_up ? 'Ditanggung Perusahaan (Gross Up)' : 'Ditanggung Karyawan';
    }
}
