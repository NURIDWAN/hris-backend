<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Free', 'Starter', 'Professional', 'Enterprise']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'price_monthly' => $this->faker->randomElement([0, 99000, 299000, 599000]),
            'price_yearly' => $this->faker->randomElement([0, 990000, 2990000, 5990000]),
            'max_employees' => $this->faker->randomElement([5, 25, 100, 0]),
            'max_users' => $this->faker->randomElement([1, 5, 20, 0]),
            'features' => ['feature1', 'feature2', 'feature3'],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Free',
            'slug' => 'free',
            'price_monthly' => 0,
            'price_yearly' => 0,
            'max_employees' => 5,
            'max_users' => 1,
        ]);
    }

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'max_employees' => 25,
            'max_users' => 5,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Professional',
            'slug' => 'professional',
            'price_monthly' => 299000,
            'price_yearly' => 2990000,
            'max_employees' => 100,
            'max_users' => 20,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'price_monthly' => 599000,
            'price_yearly' => 5990000,
            'max_employees' => 0, // unlimited
            'max_users' => 0, // unlimited
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
