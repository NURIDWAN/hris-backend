<?php

namespace Database\Factories;

use App\Models\Company;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = Company::class;

    public function definition(): array
    {
        $name = 'PT '.$this->randomCompanyName();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->randomNumber(5),
            'email' => $this->uniqueEmail('company'),
            'phone' => $this->randomPhone(),
            'address' => $this->randomAddress(),
            'logo' => null,
            'website' => $this->randomDomainName(),
            'npwp' => $this->randomNpwp(),
            'is_active' => true,
            'subscription_plan' => $this->randomElement(['trial', 'starter', 'professional', 'enterprise']),
            'subscription_ends_at' => $this->randomDateBetween('now', '+1 year'),
            'max_employees' => $this->randomElement([10, 25, 50, 100, 500]),
            'settings' => [
                'currency' => 'IDR',
                'date_format' => 'd/m/Y',
            ],
            'timezone' => 'Asia/Jakarta',
        ];
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'trial',
            'subscription_ends_at' => now()->addDays(14),
            'max_employees' => 10,
        ]);
    }

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'starter',
            'max_employees' => 25,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'professional',
            'max_employees' => 100,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_plan' => 'enterprise',
            'max_employees' => 500,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_ends_at' => now()->subDay(),
        ]);
    }
}
