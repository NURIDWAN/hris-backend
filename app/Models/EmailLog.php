<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'to',
        'from',
        'cc',
        'bcc',
        'subject',
        'body',
        'status',
        'mailer',
        'provider_message_id',
        'error_message',
        'headers',
        'mailable_class',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSending($query)
    {
        return $query->where('status', 'sending');
    }
}
