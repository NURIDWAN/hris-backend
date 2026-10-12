<?php

namespace Database\Seeders;

use App\Models\PaymentGatewaySetting;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'gateway' => PaymentGatewaySetting::GATEWAY_XENDIT,
                'name' => 'Xendit',
                'environment' => PaymentGatewaySetting::ENV_SANDBOX,
                'credentials' => [
                    'secret_key' => '', // Fill with your Xendit API Key
                    'callback_token' => '', // Fill with your Xendit Callback Token
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'gateway' => PaymentGatewaySetting::GATEWAY_MIDTRANS,
                'name' => 'Midtrans',
                'environment' => PaymentGatewaySetting::ENV_SANDBOX,
                'credentials' => [
                    'server_key' => '', // Fill with your Midtrans Server Key
                    'client_key' => '', // Fill with your Midtrans Client Key
                    'merchant_id' => '',
                ],
                'is_active' => false,
                'sort_order' => 2,
            ],
            [
                'gateway' => PaymentGatewaySetting::GATEWAY_MANUAL,
                'name' => 'Transfer Bank Manual',
                'environment' => PaymentGatewaySetting::ENV_PRODUCTION,
                'credentials' => [
                    'bank_name' => 'BCA',
                    'account_number' => '1234567890',
                    'account_name' => 'PT. JagoGaji Indonesia',
                ],
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGatewaySetting::updateOrCreate(
                ['gateway' => $gateway['gateway']],
                $gateway
            );
        }
    }
}
