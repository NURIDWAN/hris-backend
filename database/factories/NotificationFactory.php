<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    use GeneratesRandomData;

    public function definition(): array
    {
        $types = ['leave_request', 'payroll', 'attendance', 'employee', 'approval', 'info'];

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'title' => $this->randomSentence(4),
            'message' => $this->randomSentence(8),
            'type' => $this->randomElement($types),
            'link' => null,
            'data' => null,
            'read_at' => null,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function leaveRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'leave_request',
            'title' => 'Pengajuan cuti baru',
            'message' => $this->randomFullName().' mengajukan cuti',
        ]);
    }

    public function payroll(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'payroll',
            'title' => 'Slip gaji tersedia',
            'message' => 'Slip gaji bulan '.now()->format('F Y').' sudah tersedia',
        ]);
    }

    public function attendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'attendance',
            'title' => 'Pengingat absensi',
            'message' => 'Jangan lupa untuk melakukan clock in hari ini',
        ]);
    }
}
