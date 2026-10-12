<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeFaceEmbedding extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'embedding_data',
        'enrollment_photo',
        'enrolled_at',
        'enrolled_by',
        'quality_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'embedding_data' => 'array',
            'enrolled_at' => 'datetime',
            'quality_score' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function enrolledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enrolled_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
