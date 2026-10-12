<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDocument extends Model
{
    use HasFactory, LogsActivityTrait, SoftDeletes;

    public const TYPE_KTP = 'ktp';

    public const TYPE_NPWP = 'npwp';

    public const TYPE_KK = 'kk';

    public const TYPE_BPJS_KESEHATAN = 'bpjs_kesehatan';

    public const TYPE_BPJS_KETENAGAKERJAAN = 'bpjs_ketenagakerjaan';

    public const TYPE_IJAZAH = 'ijazah';

    public const TYPE_SERTIFIKAT = 'sertifikat';

    public const TYPE_KONTRAK_KERJA = 'kontrak_kerja';

    public const TYPE_OTHER = 'other';

    public const DOCUMENT_TYPES = [
        self::TYPE_KTP => 'KTP',
        self::TYPE_NPWP => 'NPWP',
        self::TYPE_KK => 'Kartu Keluarga',
        self::TYPE_BPJS_KESEHATAN => 'BPJS Kesehatan',
        self::TYPE_BPJS_KETENAGAKERJAAN => 'BPJS Ketenagakerjaan',
        self::TYPE_IJAZAH => 'Ijazah',
        self::TYPE_SERTIFIKAT => 'Sertifikat',
        self::TYPE_KONTRAK_KERJA => 'Kontrak Kerja',
        self::TYPE_OTHER => 'Lainnya',
    ];

    protected $fillable = [
        'company_id',
        'employee_id',
        'document_type',
        'document_number',
        'document_name',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'issue_date',
        'expiry_date',
        'is_verified',
        'verified_by',
        'verified_at',
        'uploaded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->where('is_verified', false);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now()->startOfDay());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '>=', now()->startOfDay())
            ->where('expiry_date', '<=', now()->addDays($days)->endOfDay());
    }

    // Accessors

    public function getDocumentTypeLabelAttribute(): string
    {
        return self::DOCUMENT_TYPES[$this->document_type] ?? ucfirst($this->document_type);
    }

    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 0).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0).' KB';
        }

        return $bytes.' B';
    }

    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isBetween(now(), now()->addDays(30));
    }

    // Methods

    public function verify(User $verifier): bool
    {
        return $this->update([
            'is_verified' => true,
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);
    }

    public function unverify(): bool
    {
        return $this->update([
            'is_verified' => false,
            'verified_by' => null,
            'verified_at' => null,
        ]);
    }

    public static function getDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }
}
