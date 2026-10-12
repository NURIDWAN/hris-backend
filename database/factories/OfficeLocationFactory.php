<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OfficeLocation;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfficeLocation>
 */
class OfficeLocationFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = OfficeLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Jakarta area coordinates
        $jakartaLat = -6.200000;
        $jakartaLng = 106.816666;

        return [
            'company_id' => Company::factory(),
            'name' => $this->randomCompanyName().' Office',
            'code' => strtoupper(substr($this->randomElement(['HQ', 'BR', 'DC', 'WH']), 0, 3).rand(100, 999)),
            'address' => $this->randomStreet(),
            'city' => $this->randomCity(),
            'province' => $this->randomElement([
                'DKI Jakarta',
                'Jawa Barat',
                'Jawa Tengah',
                'Jawa Timur',
                'Banten',
            ]),
            'latitude' => $jakartaLat + $this->randomFloat(-0.1, 0.1, 6),
            'longitude' => $jakartaLng + $this->randomFloat(-0.1, 0.1, 6),
            'radius' => $this->randomElement([50, 100, 150, 200]),
            'is_active' => true,
            'is_headquarters' => false,
        ];
    }

    /**
     * Indicate office is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate office is headquarters.
     */
    public function headquarters(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_headquarters' => true,
            'name' => 'Headquarters',
            'code' => 'HQ',
        ]);
    }

    /**
     * For a specific company.
     */
    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }
}
