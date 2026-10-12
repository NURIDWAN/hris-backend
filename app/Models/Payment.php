<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUSES = [
        self::STATUS_PENDING => 'Menunggu',
        self::STATUS_SUCCESS => 'Berhasil',
        self::STATUS_FAILED => 'Gagal',
        self::STATUS_EXPIRED => 'Kedaluwarsa',
        self::STATUS_REFUNDED => 'Dikembalikan',
    ];

    public const METHOD_BANK_TRANSFER = 'bank_transfer';

    public const METHOD_CREDIT_CARD = 'credit_card';

    public const METHOD_EWALLET = 'ewallet';

    public const METHOD_QRIS = 'qris';

    public const METHOD_MANUAL = 'manual';

    public const METHODS = [
        self::METHOD_BANK_TRANSFER => 'Transfer Bank',
        self::METHOD_CREDIT_CARD => 'Kartu Kredit',
        self::METHOD_EWALLET => 'E-Wallet',
        self::METHOD_QRIS => 'QRIS',
        self::METHOD_MANUAL => 'Transfer Manual',
    ];

    protected $fillable = [
        'company_id',
        'subscription_id',
        'invoice_id',
        'payment_number',
        'gateway',
        'gateway_transaction_id',
        'gateway_order_id',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'status',
        'payment_method',
        'payment_channel',
        'gateway_response',
        'description',
        'billing_cycle',
        'paid_at',
        'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'gateway_response' => 'array',
            'paid_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }
            if (empty($payment->net_amount)) {
                $payment->net_amount = $payment->amount - $payment->fee;
            }
        });
    }

    public static function generatePaymentNumber(): string
    {
        $prefix = 'PAY';
        $date = now()->format('Ymd');
        $lastPayment = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment
            ? ((int) substr($lastPayment->payment_number, -4)) + 1
            : 1;

        return $prefix.$date.str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function markAsSuccess(): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp '.number_format($this->amount, 0, ',', '.');
    }

    public function getFormattedFeeAttribute(): string
    {
        return 'Rp '.number_format($this->fee, 0, ',', '.');
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return 'Rp '.number_format($this->net_amount, 0, ',', '.');
    }
}
