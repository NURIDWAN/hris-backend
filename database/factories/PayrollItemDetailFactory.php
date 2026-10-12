<?php

namespace Database\Factories;

use App\Models\PayrollItem;
use App\Models\PayrollItemDetail;
use App\Models\SalaryComponent;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayrollItemDetail>
 */
class PayrollItemDetailFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = PayrollItemDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->randomElement(['earning', 'deduction']);
        $categories = $type === 'earning'
            ? ['allowance', 'bonus', 'overtime', 'other']
            : ['tax', 'insurance', 'loan', 'other'];

        return [
            'payroll_item_id' => PayrollItem::factory(),
            'salary_component_id' => null,
            'component_name' => $type === 'earning'
                ? $this->randomElement(['Tunjangan Makan', 'Tunjangan Transport', 'Bonus', 'Lembur'])
                : $this->randomElement(['BPJS Kesehatan', 'BPJS Ketenagakerjaan', 'PPh 21', 'Pinjaman']),
            'component_code' => strtoupper(substr($this->randomElement(['TJG', 'BNS', 'LBR', 'BPJ', 'PJK', 'PTG']), 0, 3)),
            'type' => $type,
            'category' => $this->randomElement($categories),
            'amount' => $this->randomElement([100000, 250000, 500000, 750000, 1000000]),
            'is_taxable' => $this->randomBoolean(70),
            'notes' => null,
        ];
    }

    /**
     * Configure as earning type.
     */
    public function earning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earning',
            'category' => $this->randomElement(['allowance', 'bonus', 'overtime', 'other']),
            'component_name' => $this->randomElement(['Tunjangan Makan', 'Tunjangan Transport', 'Bonus', 'Lembur']),
        ]);
    }

    /**
     * Configure as deduction type.
     */
    public function deduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'category' => $this->randomElement(['tax', 'insurance', 'loan', 'other']),
            'component_name' => $this->randomElement(['BPJS Kesehatan', 'BPJS Ketenagakerjaan', 'PPh 21', 'Pinjaman']),
        ]);
    }

    /**
     * Configure as allowance.
     */
    public function allowance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earning',
            'category' => 'allowance',
            'component_name' => $this->randomElement(['Tunjangan Makan', 'Tunjangan Transport', 'Tunjangan Komunikasi']),
        ]);
    }

    /**
     * Configure as insurance.
     */
    public function insurance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'category' => 'insurance',
            'component_name' => $this->randomElement(['BPJS Kesehatan', 'BPJS Ketenagakerjaan']),
        ]);
    }

    /**
     * Configure as taxable.
     */
    public function taxable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_taxable' => true,
        ]);
    }

    /**
     * Configure as non-taxable.
     */
    public function nonTaxable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_taxable' => false,
        ]);
    }

    /**
     * Configure with specific amount.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }
}
