<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JkkRiskRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'risk_level',
        'description',
        'rate',
        'year',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getRiskLevelLabelAttribute(): string
    {
        return match ($this->risk_level) {
            'very_low' => 'Sangat Rendah',
            'low' => 'Rendah',
            'medium' => 'Sedang',
            'high' => 'Tinggi',
            'very_high' => 'Sangat Tinggi',
            default => $this->risk_level,
        };
    }

    public static function getDefaultRates(): array
    {
        return [
            ['risk_level' => 'very_low', 'description' => 'Perkantoran, jasa konsultasi, perdagangan', 'rate' => 0.24],
            ['risk_level' => 'low', 'description' => 'Industri ringan, restoran, hotel', 'rate' => 0.54],
            ['risk_level' => 'medium', 'description' => 'Manufaktur, pertanian, perikanan', 'rate' => 0.89],
            ['risk_level' => 'high', 'description' => 'Konstruksi, transportasi, pergudangan', 'rate' => 1.27],
            ['risk_level' => 'very_high', 'description' => 'Pertambangan, pengeboran minyak', 'rate' => 1.74],
        ];
    }
}
