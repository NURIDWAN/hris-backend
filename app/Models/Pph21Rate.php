<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pph21Rate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'min_amount',
        'max_amount',
        'rate',
        'year',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getFormattedMinAmountAttribute(): string
    {
        return 'Rp '.number_format($this->min_amount, 0, ',', '.');
    }

    public function getFormattedMaxAmountAttribute(): string
    {
        if (! $this->max_amount) {
            return 'Tak Terbatas';
        }

        return 'Rp '.number_format($this->max_amount, 0, ',', '.');
    }

    public function getRangeLabelAttribute(): string
    {
        if (! $this->max_amount) {
            return 'Di atas '.$this->formatted_min_amount;
        }

        return $this->formatted_min_amount.' - '.$this->formatted_max_amount;
    }

    public static function getDefaultRates2024(): array
    {
        return [
            ['min_amount' => 0, 'max_amount' => 60000000, 'rate' => 5],
            ['min_amount' => 60000000, 'max_amount' => 250000000, 'rate' => 15],
            ['min_amount' => 250000000, 'max_amount' => 500000000, 'rate' => 25],
            ['min_amount' => 500000000, 'max_amount' => 5000000000, 'rate' => 30],
            ['min_amount' => 5000000000, 'max_amount' => null, 'rate' => 35],
        ];
    }
}
