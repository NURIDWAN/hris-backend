<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpjsTkSetting extends Model
{
    use HasFactory;

    protected $table = 'bpjs_tk_settings';

    protected $fillable = [
        'company_id',
        'jht_company_rate',
        'jht_employee_rate',
        'jht_enabled',
        'jkk_rate',
        'jkk_risk_level',
        'jkk_enabled',
        'jkm_rate',
        'jkm_enabled',
        'jp_company_rate',
        'jp_employee_rate',
        'jp_max_salary',
        'jp_enabled',
        'max_salary_basis',
        'effective_year',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'jht_company_rate' => 'decimal:2',
        'jht_employee_rate' => 'decimal:2',
        'jht_enabled' => 'boolean',
        'jkk_rate' => 'decimal:2',
        'jkk_enabled' => 'boolean',
        'jkm_rate' => 'decimal:2',
        'jkm_enabled' => 'boolean',
        'jp_company_rate' => 'decimal:2',
        'jp_employee_rate' => 'decimal:2',
        'jp_max_salary' => 'decimal:2',
        'jp_enabled' => 'boolean',
        'max_salary_basis' => 'decimal:2',
        'effective_year' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // JHT (Jaminan Hari Tua)
    public function getJhtTotalRateAttribute(): float
    {
        return $this->jht_company_rate + $this->jht_employee_rate;
    }

    // JP (Jaminan Pensiun)
    public function getJpTotalRateAttribute(): float
    {
        return $this->jp_company_rate + $this->jp_employee_rate;
    }

    // Total rate ditanggung perusahaan
    public function getTotalCompanyRateAttribute(): float
    {
        $total = 0;
        if ($this->jht_enabled) {
            $total += $this->jht_company_rate;
        }
        if ($this->jkk_enabled) {
            $total += $this->jkk_rate;
        }
        if ($this->jkm_enabled) {
            $total += $this->jkm_rate;
        }
        if ($this->jp_enabled) {
            $total += $this->jp_company_rate;
        }

        return $total;
    }

    // Total rate ditanggung karyawan
    public function getTotalEmployeeRateAttribute(): float
    {
        $total = 0;
        if ($this->jht_enabled) {
            $total += $this->jht_employee_rate;
        }
        if ($this->jp_enabled) {
            $total += $this->jp_employee_rate;
        }

        return $total;
    }

    public function getJkkRiskLevelLabelAttribute(): string
    {
        return match ($this->jkk_risk_level) {
            'very_low' => 'Sangat Rendah (0.24%)',
            'low' => 'Rendah (0.54%)',
            'medium' => 'Sedang (0.89%)',
            'high' => 'Tinggi (1.27%)',
            'very_high' => 'Sangat Tinggi (1.74%)',
            default => $this->jkk_risk_level,
        };
    }

    public static function getJkkRiskOptions(): array
    {
        return [
            'very_low' => ['label' => 'Sangat Rendah', 'rate' => 0.24, 'description' => 'Perkantoran, jasa konsultasi'],
            'low' => ['label' => 'Rendah', 'rate' => 0.54, 'description' => 'Perdagangan, restoran'],
            'medium' => ['label' => 'Sedang', 'rate' => 0.89, 'description' => 'Manufaktur ringan, pertanian'],
            'high' => ['label' => 'Tinggi', 'rate' => 1.27, 'description' => 'Konstruksi, transportasi'],
            'very_high' => ['label' => 'Sangat Tinggi', 'rate' => 1.74, 'description' => 'Pertambangan, pengeboran'],
        ];
    }

    public function getFormattedJpMaxSalaryAttribute(): string
    {
        return 'Rp '.number_format($this->jp_max_salary, 0, ',', '.');
    }

    // Calculate employee contribution
    public function calculateEmployeeContribution(float $salary): array
    {
        $result = [
            'jht' => 0,
            'jp' => 0,
            'total' => 0,
        ];

        if ($this->jht_enabled) {
            $result['jht'] = $salary * ($this->jht_employee_rate / 100);
        }

        if ($this->jp_enabled) {
            $jpSalary = min($salary, $this->jp_max_salary);
            $result['jp'] = $jpSalary * ($this->jp_employee_rate / 100);
        }

        $result['total'] = $result['jht'] + $result['jp'];

        return $result;
    }

    // Calculate company contribution
    public function calculateCompanyContribution(float $salary): array
    {
        $result = [
            'jht' => 0,
            'jkk' => 0,
            'jkm' => 0,
            'jp' => 0,
            'total' => 0,
        ];

        if ($this->jht_enabled) {
            $result['jht'] = $salary * ($this->jht_company_rate / 100);
        }

        if ($this->jkk_enabled) {
            $result['jkk'] = $salary * ($this->jkk_rate / 100);
        }

        if ($this->jkm_enabled) {
            $result['jkm'] = $salary * ($this->jkm_rate / 100);
        }

        if ($this->jp_enabled) {
            $jpSalary = min($salary, $this->jp_max_salary);
            $result['jp'] = $jpSalary * ($this->jp_company_rate / 100);
        }

        $result['total'] = $result['jht'] + $result['jkk'] + $result['jkm'] + $result['jp'];

        return $result;
    }
}
