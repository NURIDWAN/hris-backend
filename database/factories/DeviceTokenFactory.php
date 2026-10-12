<?php

namespace Database\Factories;

use App\Models\DeviceToken;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceToken>
 */
class DeviceTokenFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = DeviceToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'token' => \Illuminate\Support\Str::uuid()->toString().':'.hash('sha256', random_bytes(32)),
            'platform' => $this->randomElement(['android', 'ios', 'web']),
            'device_name' => $this->randomElement([
                'iPhone 14 Pro',
                'iPhone 13',
                'Samsung Galaxy S23',
                'Google Pixel 7',
                'Chrome Browser',
            ]),
            'device_model' => $this->randomElement([
                'iPhone14,3',
                'iPhone14,2',
                'SM-S918B',
                'Pixel 7',
                'Chrome/119',
            ]),
            'app_version' => $this->numerify('#.#.#'),
            'is_active' => true,
            'last_used_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function android(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'android',
            'device_name' => 'Samsung Galaxy S23',
            'device_model' => 'SM-S918B',
        ]);
    }

    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'ios',
            'device_name' => 'iPhone 14 Pro',
            'device_model' => 'iPhone14,3',
        ]);
    }

    public function web(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'web',
            'device_name' => 'Chrome Browser',
            'device_model' => 'Chrome/119',
        ]);
    }
}
