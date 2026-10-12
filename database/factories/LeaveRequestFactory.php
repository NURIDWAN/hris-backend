<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = LeaveRequest::class;

    public function definition(): array
    {
        $startDate = now()->addDays(rand(7, 30));
        $daysToAdd = rand(1, 5);
        $endDate = (clone $startDate)->addDays($daysToAdd);
        $totalDays = $daysToAdd + 1;

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'request_number' => 'LV'.date('Ym').str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'is_half_day' => false,
            'half_day_type' => null,
            'reason' => $this->randomSentence(),
            'attachment' => null,
            'emergency_contact' => $this->randomBoolean() ? $this->randomPhone() : null,
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'approval_notes' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ];
    }

    public function forDates(string $startDate, string $endDate): static
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $totalDays = $start->diff($end)->days + 1;

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
        ]);
    }

    public function singleDay(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $date,
            'end_date' => $date,
            'total_days' => 1,
            'is_half_day' => false,
        ]);
    }

    public function halfDay(string $date, string $type = 'morning'): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => $date,
            'end_date' => $date,
            'total_days' => 0.5,
            'is_half_day' => true,
            'half_day_type' => $type,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'approved_by' => null,
            'approved_at' => null,
            'rejected_by' => null,
            'rejected_at' => null,
        ]);
    }

    public function approved(?User $approver = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => $approver?->id ?? User::factory(),
            'approved_at' => now(),
            'approval_notes' => $this->randomBoolean() ? $this->randomSentence() : null,
        ]);
    }

    public function rejected(?User $rejector = null, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'rejected_by' => $rejector?->id ?? User::factory(),
            'rejected_at' => now(),
            'rejection_reason' => $reason ?? $this->randomSentence(),
        ]);
    }

    public function cancelled(?User $canceller = null, ?string $reason = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_by' => $canceller?->id ?? User::factory(),
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? $this->randomSentence(),
        ]);
    }

    public function withAttachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'attachment' => 'attachments/leave/'.\Illuminate\Support\Str::uuid()->toString().'.pdf',
        ]);
    }

    public function upcoming(): static
    {
        $startDate = now()->addDays(rand(7, 30));
        $endDate = (clone $startDate)->addDays(rand(1, 5));

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $startDate->diffInDays($endDate) + 1,
            'status' => 'approved',
        ]);
    }

    public function ongoing(): static
    {
        $startDate = now()->subDays(2);
        $endDate = now()->addDays(3);

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => 6,
            'status' => 'approved',
        ]);
    }

    public function past(): static
    {
        $startDate = now()->subDays(14);
        $endDate = now()->subDays(10);

        return $this->state(fn (array $attributes) => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => 5,
            'status' => 'approved',
        ]);
    }
}
