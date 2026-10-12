<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtkpSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'status_code',
        'description',
        'annual_amount',
        'year',
        'is_active',
    ];

    protected $casts = [
        'annual_amount' => 'decimal:2',
        'year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getMonthlyAmountAttribute(): float
    {
        return $this->annual_amount / 12;
    }

    public function getFormattedAnnualAmountAttribute(): string
    {
        return 'Rp '.number_format($this->annual_amount, 0, ',', '.');
    }

    public function getFormattedMonthlyAmountAttribute(): string
    {
        return 'Rp '.number_format($this->monthly_amount, 0, ',', '.');
    }

    public static function getStatusOptions(): array
    {
        return [
            'TK/0' => 'Tidak Kawin, Tanpa Tanggungan',
            'TK/1' => 'Tidak Kawin, 1 Tanggungan',
            'TK/2' => 'Tidak Kawin, 2 Tanggungan',
            'TK/3' => 'Tidak Kawin, 3 Tanggungan',
            'K/0' => 'Kawin, Tanpa Tanggungan',
            'K/1' => 'Kawin, 1 Tanggungan',
            'K/2' => 'Kawin, 2 Tanggungan',
            'K/3' => 'Kawin, 3 Tanggungan',
            'K/I/0' => 'Kawin, Penghasilan Istri Digabung, Tanpa Tanggungan',
            'K/I/1' => 'Kawin, Penghasilan Istri Digabung, 1 Tanggungan',
            'K/I/2' => 'Kawin, Penghasilan Istri Digabung, 2 Tanggungan',
            'K/I/3' => 'Kawin, Penghasilan Istri Digabung, 3 Tanggungan',
        ];
    }

    public static function getDefaultPtkp2024(): array
    {
        return [
            ['status_code' => 'TK/0', 'description' => 'Tidak Kawin, Tanpa Tanggungan', 'annual_amount' => 54000000],
            ['status_code' => 'TK/1', 'description' => 'Tidak Kawin, 1 Tanggungan', 'annual_amount' => 58500000],
            ['status_code' => 'TK/2', 'description' => 'Tidak Kawin, 2 Tanggungan', 'annual_amount' => 63000000],
            ['status_code' => 'TK/3', 'description' => 'Tidak Kawin, 3 Tanggungan', 'annual_amount' => 67500000],
            ['status_code' => 'K/0', 'description' => 'Kawin, Tanpa Tanggungan', 'annual_amount' => 58500000],
            ['status_code' => 'K/1', 'description' => 'Kawin, 1 Tanggungan', 'annual_amount' => 63000000],
            ['status_code' => 'K/2', 'description' => 'Kawin, 2 Tanggungan', 'annual_amount' => 67500000],
            ['status_code' => 'K/3', 'description' => 'Kawin, 3 Tanggungan', 'annual_amount' => 72000000],
            ['status_code' => 'K/I/0', 'description' => 'Kawin, Penghasilan Istri Digabung, Tanpa Tanggungan', 'annual_amount' => 112500000],
            ['status_code' => 'K/I/1', 'description' => 'Kawin, Penghasilan Istri Digabung, 1 Tanggungan', 'annual_amount' => 117000000],
            ['status_code' => 'K/I/2', 'description' => 'Kawin, Penghasilan Istri Digabung, 2 Tanggungan', 'annual_amount' => 121500000],
            ['status_code' => 'K/I/3', 'description' => 'Kawin, Penghasilan Istri Digabung, 3 Tanggungan', 'annual_amount' => 126000000],
        ];
    }
}
