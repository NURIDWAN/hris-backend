<?php

namespace Database\Factories;

use App\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        return [
            'to' => fake()->safeEmail(),
            'from' => fake()->safeEmail(),
            'cc' => null,
            'bcc' => null,
            'subject' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'status' => 'sent',
            'mailer' => 'smtp',
            'provider_message_id' => null,
            'error_message' => null,
            'headers' => null,
            'mailable_class' => null,
            'sent_at' => now(),
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => 'failed',
            'error_message' => 'Connection refused',
            'sent_at' => null,
        ]);
    }

    public function sending(): static
    {
        return $this->state(fn () => [
            'status' => 'sending',
            'sent_at' => null,
        ]);
    }
}
