<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

class LogEmailActivity
{
    public function handleSending(MessageSending $event): void
    {
        $message = $event->message;

        $to = collect($message->getTo())->map(fn ($a) => $a->getAddress())->implode(', ');
        $from = collect($message->getFrom())->map(fn ($a) => $a->getAddress())->first();
        $cc = collect($message->getCc())->map(fn ($a) => $a->getAddress())->implode(', ') ?: null;
        $bcc = collect($message->getBcc())->map(fn ($a) => $a->getAddress())->implode(', ') ?: null;

        EmailLog::create([
            'to' => $to,
            'from' => $from,
            'cc' => $cc,
            'bcc' => $bcc,
            'subject' => $message->getSubject() ?? '',
            'body' => $message->getHtmlBody() ?? $message->getTextBody(),
            'status' => 'sending',
            'mailer' => config('mail.default'),
            'mailable_class' => $event->data['__mailable'] ?? null,
        ]);
    }

    public function handleSent(MessageSent $event): void
    {
        $message = $event->message;
        $to = collect($message->getTo())->map(fn ($a) => $a->getAddress())->implode(', ');
        $subject = $message->getSubject();

        $log = EmailLog::where('to', $to)
            ->where('subject', $subject)
            ->where('status', 'sending')
            ->latest()
            ->first();

        if ($log) {
            $messageId = null;
            if ($event->sent) {
                $messageId = $event->sent->getMessageId();
            }

            $log->update([
                'status' => 'sent',
                'provider_message_id' => $messageId,
                'sent_at' => now(),
            ]);
        }
    }
}
