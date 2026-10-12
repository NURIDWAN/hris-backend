<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItemDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'salary_component_id',
        'component_name',
        'component_code',
        'type',
        'category',
        'amount',
        'is_taxable',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_taxable' => 'boolean',
        ];
    }

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function salaryComponent()
    {
        return $this->belongsTo(SalaryComponent::class);
    }

    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }

    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->isDeduction() ? '-' : '+';

        return $prefix.' Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'earning' => 'Pendapatan',
            'deduction' => 'Potongan',
            default => '-',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'fixed' => 'Tetap',
            'variable' => 'Variabel',
            'benefit' => 'Tunjangan',
            'tax' => 'Pajak',
            'insurance' => 'Asuransi',
            'bpjs' => 'BPJS',
            'loan' => 'Pinjaman',
            'overtime' => 'Lembur',
            'penalty' => 'Denda',
            'reimbursement' => 'Reimbursement',
            'other' => 'Lainnya',
            default => '-',
        };
    }
}
