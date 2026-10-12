<?php

namespace Database\Factories;

use App\Models\Company;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    use GeneratesRandomData;

    protected static ?string $password;

    public function definition(): array
    {
        $firstName = $this->randomFirstName();
        $lastName = $this->randomLastName();
        $fullName = $firstName.' '.$lastName;

        return [
            'company_id' => null,
            'name' => $fullName,
            'email' => $this->uniqueEmail(strtolower($firstName)),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'phone' => $this->randomPhone(),
            'avatar' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => null,
        ]);
    }
}
