<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = $this->randomElement([99000, 199000, 299000, 499000, 999000, 1999000]);
        $fee = round($amount * 0.029); // ~2.9% fee

        return [
            'company_id' => Company::factory(),
            'subscription_id' => null,
            'invoice_id' => null,
            'payment_number' => 'PAY'.now()->format('Ymd').$this->numerify('####'),
            'gateway' => PaymentGatewaySetting::GATEWAY_MIDTRANS,
            'gateway_transaction_id' => \Illuminate\Support\Str::uuid()->toString(),
            'gateway_order_id' => 'ORDER-'.\Illuminate\Support\Str::uuid()->toString(),
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => $amount - $fee,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'payment_method' => $this->randomElement(array_keys(Payment::METHODS)),
            'payment_channel' => $this->randomElement(['bca', 'bni', 'bri', 'mandiri', 'gopay', 'ovo']),
            'gateway_response' => null,
            'description' => 'Pembayaran langganan GajiPro',
            'billing_cycle' => 'monthly',
            'paid_at' => null,
            'expired_at' => now()->addDay(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_FAILED,
            'paid_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Payment::STATUS_EXPIRED,
            'paid_at' => null,
            'expired_at' => now()->subDay(),
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    public function forSubscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_BANK_TRANSFER,
            'payment_channel' => $this->randomElement(['bca', 'bni', 'bri', 'mandiri']),
        ]);
    }

    public function ewallet(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => Payment::METHOD_EWALLET,
            'payment_channel' => $this->randomElement(['gopay', 'ovo', 'dana', 'shopeepay']),
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGatewaySetting::GATEWAY_MANUAL,
            'payment_method' => Payment::METHOD_MANUAL,
            'payment_channel' => 'bca',
            'fee' => 0,
            'net_amount' => $attributes['amount'] ?? 99000,
        ]);
    }
}
