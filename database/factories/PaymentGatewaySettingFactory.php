<?php

namespace Database\Factories;

use App\Models\PaymentGatewaySetting;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentGatewaySetting>
 */
class PaymentGatewaySettingFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = PaymentGatewaySetting::class;

    public function definition(): array
    {
        $gateway = $this->randomElement(array_keys(PaymentGatewaySetting::GATEWAYS));

        return [
            'gateway' => $gateway,
            'name' => PaymentGatewaySetting::GATEWAYS[$gateway],
            'environment' => PaymentGatewaySetting::ENV_SANDBOX,
            'credentials' => [
                'server_key' => \Illuminate\Support\Str::uuid()->toString(),
                'client_key' => \Illuminate\Support\Str::uuid()->toString(),
            ],
            'is_active' => false,
            'sort_order' => rand(1, 10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'environment' => PaymentGatewaySetting::ENV_PRODUCTION,
        ]);
    }

    public function midtrans(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGatewaySetting::GATEWAY_MIDTRANS,
            'name' => 'Midtrans',
            'credentials' => [
                'server_key' => 'SB-Mid-server-'.\Illuminate\Support\Str::uuid()->toString(),
                'client_key' => 'SB-Mid-client-'.\Illuminate\Support\Str::uuid()->toString(),
                'merchant_id' => 'G'.$this->numerify('######'),
            ],
        ]);
    }

    public function xendit(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGatewaySetting::GATEWAY_XENDIT,
            'name' => 'Xendit',
            'is_active' => true,
            'credentials' => [
                'secret_key' => 'xnd_development_test-secret-key',
                'public_key' => 'xnd_public_development_test-public-key',
                'callback_token' => 'test-callback-token',
            ],
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => PaymentGatewaySetting::GATEWAY_MANUAL,
            'name' => 'Manual Transfer',
            'is_active' => true,
            'credentials' => [
                'bank_name' => 'BCA',
                'account_number' => $this->numerify('##########'),
                'account_name' => 'PT GajiPro Indonesia',
            ],
        ]);
    }
}
