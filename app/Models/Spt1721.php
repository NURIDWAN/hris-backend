<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SPT Tahunan PPh 21 (Formulir 1721)
 *
 * Model untuk SPT Induk yang berisi:
 * - Identitas pemotong pajak
 * - Rekapitulasi PPh 21 tahunan
 * - Status pelaporan
 */
class Spt1721 extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'spt_1721';

    protected $fillable = [
        'company_id',
        'tax_year',
        'spt_type',
        'pembetulan_ke',
        // Company data
        'company_npwp',
        'company_name',
        'company_address',
        'company_phone',
        'company_email',
        'klu_code',
        'kpp_code',
        // Signer
        'signer_name',
        'signer_npwp',
        'signer_position',
        'sign_date',
        'sign_place',
        // PPh 21 Final
        'pph21_final_bruto',
        'pph21_final_pph',
        // PPh 21 Tidak Final
        'total_employees',
        'total_bruto',
        'total_pph21_terutang',
        'total_pph21_disetor',
        'total_pph21_kurang_lebih',
        // Status
        'status',
        'ntpn',
        'submission_date',
        'submission_receipt',
        // Meta
        'created_by',
        'submitted_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'tax_year' => 'integer',
            'pembetulan_ke' => 'integer',
            'total_employees' => 'integer',
            'pph21_final_bruto' => 'decimal:2',
            'pph21_final_pph' => 'decimal:2',
            'total_bruto' => 'decimal:2',
            'total_pph21_terutang' => 'decimal:2',
            'total_pph21_disetor' => 'decimal:2',
            'total_pph21_kurang_lebih' => 'decimal:2',
            'sign_date' => 'date',
            'submission_date' => 'date',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function monthlyData(): HasMany
    {
        return $this->hasMany(Spt1721Monthly::class, 'spt_1721_id')->orderBy('month');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Spt1721Employee::class, 'spt_1721_id');
    }

    // ==================== SCOPES ====================

    public function scopeForYear($query, int $year)
    {
        return $query->where('tax_year', $year);
    }

    public function scopeNormal($query)
    {
        return $query->where('spt_type', 'normal');
    }

    public function scopePembetulan($query)
    {
        return $query->where('spt_type', 'pembetulan');
    }

    // ==================== ACCESSORS ====================

    public function getSptNumberAttribute(): string
    {
        $type = $this->spt_type === 'pembetulan' ? "-PB{$this->pembetulan_ke}" : '';

        return "SPT-1721-{$this->tax_year}{$type}";
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'calculated' => 'Sudah Dihitung',
            'submitted' => 'Sudah Disetor',
            'reported' => 'Sudah Dilaporkan',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'calculated' => 'info',
            'submitted' => 'warning',
            'reported' => 'success',
            default => 'secondary',
        };
    }

    public function getFormattedTotalBrutoAttribute(): string
    {
        return 'Rp '.number_format($this->total_bruto, 0, ',', '.');
    }

    public function getFormattedTotalPph21TerutangAttribute(): string
    {
        return 'Rp '.number_format($this->total_pph21_terutang, 0, ',', '.');
    }

    public function getFormattedTotalPph21DisetorAttribute(): string
    {
        return 'Rp '.number_format($this->total_pph21_disetor, 0, ',', '.');
    }

    public function getFormattedTotalPph21KurangLebihAttribute(): string
    {
        $amount = $this->total_pph21_kurang_lebih;
        $prefix = $amount >= 0 ? '' : '(';
        $suffix = $amount >= 0 ? '' : ')';

        return $prefix.'Rp '.number_format(abs($amount), 0, ',', '.').$suffix;
    }

    public function getKurangLebihStatusAttribute(): string
    {
        if ($this->total_pph21_kurang_lebih > 0) {
            return 'kurang_bayar';
        } elseif ($this->total_pph21_kurang_lebih < 0) {
            return 'lebih_bayar';
        }

        return 'nihil';
    }

    // ==================== HELPERS ====================

    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'calculated']);
    }

    public function canSubmit(): bool
    {
        return $this->status === 'calculated';
    }

    public function canReport(): bool
    {
        return $this->status === 'submitted';
    }

    public function recalculate(): void
    {
        // Sum from monthly data using aggregate methods
        $totalBruto = $this->monthlyData()->sum('total_bruto');
        $totalPph21Monthly = $this->monthlyData()->sum('total_pph21');
        $totalDisetor = $this->monthlyData()->sum('pph21_disetor');

        // Sum from employees using aggregate methods
        $totalEmployees = $this->employees()->count();
        $totalPph21Terutang = $this->employees()->sum('pph21_terutang');

        $this->update([
            'total_employees' => $totalEmployees,
            'total_bruto' => $totalBruto,
            'total_pph21_terutang' => $totalPph21Terutang,
            'total_pph21_disetor' => $totalDisetor,
            'total_pph21_kurang_lebih' => $totalPph21Terutang - $totalDisetor,
            'status' => 'calculated',
        ]);
    }
}
