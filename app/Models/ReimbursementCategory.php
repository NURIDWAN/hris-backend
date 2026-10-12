<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReimbursementCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'max_amount',
        'requires_receipt',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_amount' => 'decimal:2',
            'requires_receipt' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class, 'category_id');
    }

    // Accessors
    public function getFormattedMaxAmountAttribute(): string
    {
        if (! $this->max_amount) {
            return 'Tidak terbatas';
        }

        return 'Rp '.number_format($this->max_amount, 0, ',', '.');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
