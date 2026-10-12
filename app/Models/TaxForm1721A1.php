<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxForm1721A1 extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tax_form_1721a1';

    protected $fillable = [
        'company_id',
        'employee_id',
        'tax_year',
        'form_number',
        // Company data
        'company_npwp',
        'company_name',
        'company_address',
        // Employee data
        'employee_npwp',
        'employee_nik',
        'employee_name',
        'employee_address',
        'ptkp_status',
        // Work period
        'work_start_month',
        'work_end_month',
        // Penghasilan Bruto
        'gaji_pokok',
        'tunjangan_pph',
        'tunjangan_lainnya',
        'honorarium',
        'premi_asuransi',
        'natura',
        'tantiem_bonus_thr',
        'gross_salary',
        // Pengurang
        'biaya_jabatan',
        'iuran_pensiun',
        'total_pengurang',
        // Neto, PTKP, PKP
        'neto_salary',
        'ptkp',
        'pkp',
        // PPh 21
        'pph21_terutang',
        'pph21_dipotong_sebelumnya',
        'total_pph21_withheld',
        // Timestamps
        'generated_at',
        'generated_by',
    ];

    protected function casts(): array
    {
        return [
            'tax_year' => 'integer',
            'work_start_month' => 'integer',
            'work_end_month' => 'integer',
            'gaji_pokok' => 'decimal:2',
            'tunjangan_pph' => 'decimal:2',
            'tunjangan_lainnya' => 'decimal:2',
            'honorarium' => 'decimal:2',
            'premi_asuransi' => 'decimal:2',
            'natura' => 'decimal:2',
            'tantiem_bonus_thr' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'biaya_jabatan' => 'decimal:2',
            'iuran_pensiun' => 'decimal:2',
            'total_pengurang' => 'decimal:2',
            'neto_salary' => 'decimal:2',
            'ptkp' => 'decimal:2',
            'pkp' => 'decimal:2',
            'pph21_terutang' => 'decimal:2',
            'pph21_dipotong_sebelumnya' => 'decimal:2',
            'total_pph21_withheld' => 'decimal:2',
            'generated_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TaxForm1721A1 $taxForm) {
            if (empty($taxForm->form_number)) {
                $taxForm->form_number = static::generateFormNumber($taxForm);
            }
        });
    }

    public static function generateFormNumber(TaxForm1721A1 $taxForm): string
    {
        $employeeNpwp = $taxForm->employee_npwp ?? 'NONPWP';
        $npwpClean = str_replace(['.', '-'], '', $employeeNpwp);

        return "1.1-{$npwpClean}-{$taxForm->tax_year}";
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('tax_year', $year);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function getFormattedGrossSalaryAttribute(): string
    {
        return 'Rp '.number_format($this->gross_salary, 0, ',', '.');
    }

    public function getFormattedNetoSalaryAttribute(): string
    {
        return 'Rp '.number_format($this->neto_salary, 0, ',', '.');
    }

    public function getFormattedPtkpAttribute(): string
    {
        return 'Rp '.number_format($this->ptkp, 0, ',', '.');
    }

    public function getFormattedPkpAttribute(): string
    {
        return 'Rp '.number_format($this->pkp, 0, ',', '.');
    }

    public function getFormattedTotalPph21WithheldAttribute(): string
    {
        return 'Rp '.number_format($this->total_pph21_withheld, 0, ',', '.');
    }

    public function getWorkPeriodAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $start = $months[$this->work_start_month] ?? '';
        $end = $months[$this->work_end_month] ?? '';

        return "{$start} - {$end} {$this->tax_year}";
    }
}
