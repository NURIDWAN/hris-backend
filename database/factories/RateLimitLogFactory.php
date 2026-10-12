<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RateLimitLog>
 */
class RateLimitLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ip_address' => fake()->ipv4(),
            'user_id' => null,
            'route' => '/api/v1/'.fake()->word(),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }
}
