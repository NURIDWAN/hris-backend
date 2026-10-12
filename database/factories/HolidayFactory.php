<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Holiday>
 */
class HolidayFactory extends Factory
{
    use GeneratesRandomData;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->randomElement([
                'Tahun Baru',
                'Hari Raya Idul Fitri',
                'Hari Raya Idul Adha',
                'Hari Kemerdekaan',
                'Hari Natal',
                'Hari Buruh',
                'Hari Pancasila',
                'Isra Miraj',
                'Maulid Nabi',
                'Waisak',
                'Imlek',
                'Nyepi',
            ]),
            'date' => $this->randomDateBetween('now', '+1 year'),
            'type' => $this->randomElement(['national', 'company', 'religious']),
            'description' => $this->randomBoolean() ? $this->randomSentence() : null,
            'is_recurring' => $this->randomBoolean(30),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function national(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'national',
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
        ]);
    }

    public function religious(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'religious',
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
