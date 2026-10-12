<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomElement([99000, 299000, 599000, 990000, 2990000]);
        $tax = $subtotal * 0.11; // PPN 11%
        $discount = 0;
        $total = $subtotal + $tax - $discount;

        return [
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'company_id' => Company::factory(),
            'subscription_id' => null,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'currency' => 'IDR',
            'description' => 'Langganan GajiPro',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
            'paid_at' => null,
            'payment_method' => null,
            'payment_details' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => 'bank_transfer',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_CANCELLED,
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PENDING,
            'due_at' => now()->subDays(3),
        ]);
    }

    public function withSubscription(?Subscription $subscription = null): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_id' => $subscription?->id ?? Subscription::factory(),
        ]);
    }
}
