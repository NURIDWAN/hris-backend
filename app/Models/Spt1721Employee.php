<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran 1721-II - Daftar Bukti Potong Karyawan
 *
 * Berisi daftar semua karyawan yang dipotong PPh 21:
 * - Data identitas karyawan
 * - Penghasilan dan pajak tahunan
 * - Link ke bukti potong 1721-A1
 */
class Spt1721Employee extends Model
{
    protected $table = 'spt_1721_employees';

    protected $fillable = [
        'spt_1721_id',
        'employee_id',
        'tax_form_1721a1_id',
        // Employee data
        'employee_npwp',
        'employee_nik',
        'employee_name',
        'ptkp_status',
        'work_start_month',
        'work_end_month',
        // Penghasilan & Pajak
        'gross_salary',
        'neto_salary',
        'ptkp',
        'pkp',
        'pph21_terutang',
        'pph21_ditanggung_pemerintah',
        'pph21_telah_dipotong',
        'pph21_kurang_lebih',
        // Status
        'employee_type',
        'exit_date',
        'exit_reason',
    ];

    protected function casts(): array
    {
        return [
            'work_start_month' => 'integer',
            'work_end_month' => 'integer',
            'gross_salary' => 'decimal:2',
            'neto_salary' => 'decimal:2',
            'ptkp' => 'decimal:2',
            'pkp' => 'decimal:2',
            'pph21_terutang' => 'decimal:2',
            'pph21_ditanggung_pemerintah' => 'decimal:2',
            'pph21_telah_dipotong' => 'decimal:2',
            'pph21_kurang_lebih' => 'decimal:2',
            'exit_date' => 'date',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function spt(): BelongsTo
    {
        return $this->belongsTo(Spt1721::class, 'spt_1721_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function taxForm(): BelongsTo
    {
        return $this->belongsTo(TaxForm1721A1::class, 'tax_form_1721a1_id');
    }

    // ==================== ACCESSORS ====================

    public function getWorkPeriodAttribute(): string
    {
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
        ];

        $start = $months[$this->work_start_month] ?? '';
        $end = $months[$this->work_end_month] ?? '';

        return "{$start} - {$end}";
    }

    public function getWorkingMonthsAttribute(): int
    {
        return $this->work_end_month - $this->work_start_month + 1;
    }

    public function getEmployeeTypeLabelAttribute(): string
    {
        return match ($this->employee_type) {
            'tetap' => 'Pegawai Tetap',
            'tidak_tetap' => 'Pegawai Tidak Tetap',
            'keluar' => 'Keluar/Resign',
            default => $this->employee_type,
        };
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

    public function getFormattedPph21TerutangAttribute(): string
    {
        return 'Rp '.number_format($this->pph21_terutang, 0, ',', '.');
    }

    public function getFormattedPph21TelahDipotongAttribute(): string
    {
        return 'Rp '.number_format($this->pph21_telah_dipotong, 0, ',', '.');
    }

    public function getHasNpwpAttribute(): bool
    {
        return ! empty($this->employee_npwp) && $this->employee_npwp !== '00.000.000.0-000.000';
    }

    // ==================== HELPERS ====================

    /**
     * Sync data from TaxForm1721A1
     */
    public function syncFromTaxForm(TaxForm1721A1 $taxForm): void
    {
        $this->update([
            'tax_form_1721a1_id' => $taxForm->id,
            'employee_npwp' => $taxForm->employee_npwp,
            'employee_nik' => $taxForm->employee_nik,
            'employee_name' => $taxForm->employee_name,
            'ptkp_status' => $taxForm->ptkp_status,
            'work_start_month' => $taxForm->work_start_month,
            'work_end_month' => $taxForm->work_end_month,
            'gross_salary' => $taxForm->gross_salary,
            'neto_salary' => $taxForm->neto_salary,
            'ptkp' => $taxForm->ptkp,
            'pkp' => $taxForm->pkp,
            'pph21_terutang' => $taxForm->pph21_terutang,
            'pph21_telah_dipotong' => $taxForm->total_pph21_withheld,
            'pph21_kurang_lebih' => $taxForm->pph21_terutang - $taxForm->total_pph21_withheld,
        ]);
    }
}
