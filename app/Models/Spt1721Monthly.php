<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lampiran 1721-I - Data Bulanan PPh 21
 *
 * Berisi rekapitulasi PPh 21 per bulan:
 * - Jumlah penerima penghasilan
 * - Penghasilan bruto per kategori
 * - PPh 21 terutang per kategori
 * - Data penyetoran (NTPN)
 */
class Spt1721Monthly extends Model
{
    protected $table = 'spt_1721_monthly';

    protected $fillable = [
        'spt_1721_id',
        'month',
        // Jumlah Penerima
        'employee_count',
        'employee_count_npwp',
        'employee_count_non_npwp',
        // Bruto
        'bruto_pegawai_tetap',
        'bruto_pegawai_tidak_tetap',
        'bruto_bukan_pegawai',
        'bruto_pesangon',
        'bruto_honorarium',
        'bruto_lainnya',
        'total_bruto',
        // PPh 21
        'pph21_pegawai_tetap',
        'pph21_pegawai_tidak_tetap',
        'pph21_bukan_pegawai',
        'pph21_pesangon',
        'pph21_honorarium',
        'pph21_lainnya',
        'total_pph21',
        // Penyetoran
        'pph21_disetor',
        'ssp_ntpn',
        'ssp_date',
        // Selisih
        'pph21_kurang_lebih',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'employee_count' => 'integer',
            'employee_count_npwp' => 'integer',
            'employee_count_non_npwp' => 'integer',
            'bruto_pegawai_tetap' => 'decimal:2',
            'bruto_pegawai_tidak_tetap' => 'decimal:2',
            'bruto_bukan_pegawai' => 'decimal:2',
            'bruto_pesangon' => 'decimal:2',
            'bruto_honorarium' => 'decimal:2',
            'bruto_lainnya' => 'decimal:2',
            'total_bruto' => 'decimal:2',
            'pph21_pegawai_tetap' => 'decimal:2',
            'pph21_pegawai_tidak_tetap' => 'decimal:2',
            'pph21_bukan_pegawai' => 'decimal:2',
            'pph21_pesangon' => 'decimal:2',
            'pph21_honorarium' => 'decimal:2',
            'pph21_lainnya' => 'decimal:2',
            'total_pph21' => 'decimal:2',
            'pph21_disetor' => 'decimal:2',
            'ssp_date' => 'date',
            'pph21_kurang_lebih' => 'decimal:2',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function spt(): BelongsTo
    {
        return $this->belongsTo(Spt1721::class, 'spt_1721_id');
    }

    // ==================== ACCESSORS ====================

    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $months[$this->month] ?? '';
    }

    public function getPeriodAttribute(): string
    {
        return $this->month_name.' '.$this->spt?->tax_year;
    }

    public function getFormattedTotalBrutoAttribute(): string
    {
        return 'Rp '.number_format($this->total_bruto, 0, ',', '.');
    }

    public function getFormattedTotalPph21Attribute(): string
    {
        return 'Rp '.number_format($this->total_pph21, 0, ',', '.');
    }

    public function getFormattedPph21DisetorAttribute(): string
    {
        return 'Rp '.number_format($this->pph21_disetor, 0, ',', '.');
    }

    public function getHasNtpnAttribute(): bool
    {
        return ! empty($this->ssp_ntpn);
    }

    // ==================== HELPERS ====================

    public function recalculateTotals(): void
    {
        $this->total_bruto = $this->bruto_pegawai_tetap +
            $this->bruto_pegawai_tidak_tetap +
            $this->bruto_bukan_pegawai +
            $this->bruto_pesangon +
            $this->bruto_honorarium +
            $this->bruto_lainnya;

        $this->total_pph21 = $this->pph21_pegawai_tetap +
            $this->pph21_pegawai_tidak_tetap +
            $this->pph21_bukan_pegawai +
            $this->pph21_pesangon +
            $this->pph21_honorarium +
            $this->pph21_lainnya;

        $this->pph21_kurang_lebih = $this->total_pph21 - $this->pph21_disetor;

        $this->save();
    }
}
