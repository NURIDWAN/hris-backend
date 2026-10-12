<?php

use App\Models\Company;
use App\Models\SalaryComponent;

beforeEach(function () {
    $this->company = Company::factory()->create();
});

describe('SalaryComponent Model', function () {
    test('can create salary component', function () {
        $component = SalaryComponent::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Tunjangan Transport',
            'code' => 'TT',
            'type' => 'earning',
        ]);

        expect($component)->toBeInstanceOf(SalaryComponent::class);
        expect($component->name)->toBe('Tunjangan Transport');
        expect($component->code)->toBe('TT');
        expect($component->type)->toBe('earning');
    });

    test('belongs to company', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        expect($component->company)->toBeInstanceOf(Company::class);
        expect($component->company->id)->toBe($this->company->id);
    });
});

describe('SalaryComponent Scopes', function () {
    test('active scope filters active components', function () {
        SalaryComponent::factory()->count(2)->create(['company_id' => $this->company->id, 'is_active' => true]);
        SalaryComponent::factory()->inactive()->create(['company_id' => $this->company->id]);

        $activeComponents = SalaryComponent::where('company_id', $this->company->id)->active()->get();

        expect($activeComponents)->toHaveCount(2);
        expect($activeComponents->every(fn ($c) => $c->is_active))->toBeTrue();
    });

    test('earnings scope filters earning type', function () {
        SalaryComponent::factory()->earning()->count(2)->create(['company_id' => $this->company->id]);
        SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);

        $earnings = SalaryComponent::where('company_id', $this->company->id)->earnings()->get();

        expect($earnings)->toHaveCount(2);
        expect($earnings->every(fn ($c) => $c->type === 'earning'))->toBeTrue();
    });

    test('deductions scope filters deduction type', function () {
        SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);
        SalaryComponent::factory()->deduction()->count(2)->create(['company_id' => $this->company->id]);

        $deductions = SalaryComponent::where('company_id', $this->company->id)->deductions()->get();

        expect($deductions)->toHaveCount(2);
        expect($deductions->every(fn ($c) => $c->type === 'deduction'))->toBeTrue();
    });

    test('mandatory scope filters mandatory components', function () {
        SalaryComponent::factory()->mandatory()->count(2)->create(['company_id' => $this->company->id]);
        SalaryComponent::factory()->create(['company_id' => $this->company->id, 'is_mandatory' => false]);

        $mandatory = SalaryComponent::where('company_id', $this->company->id)->mandatory()->get();

        expect($mandatory)->toHaveCount(2);
        expect($mandatory->every(fn ($c) => $c->is_mandatory))->toBeTrue();
    });

    test('taxable scope filters taxable components', function () {
        SalaryComponent::factory()->taxable()->count(2)->create(['company_id' => $this->company->id]);
        SalaryComponent::factory()->nonTaxable()->create(['company_id' => $this->company->id]);

        $taxable = SalaryComponent::where('company_id', $this->company->id)->taxable()->get();

        expect($taxable)->toHaveCount(2);
        expect($taxable->every(fn ($c) => $c->is_taxable))->toBeTrue();
    });
});

describe('SalaryComponent Helpers', function () {
    test('isEarning returns true for earning type', function () {
        $component = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);

        expect($component->isEarning())->toBeTrue();
        expect($component->isDeduction())->toBeFalse();
    });

    test('isDeduction returns true for deduction type', function () {
        $component = SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);

        expect($component->isDeduction())->toBeTrue();
        expect($component->isEarning())->toBeFalse();
    });

    test('calculateAmount returns fixed amount', function () {
        $component = SalaryComponent::factory()->fixed(500000)->create(['company_id' => $this->company->id]);

        expect($component->calculateAmount(10000000))->toBe(500000.0);
    });

    test('calculateAmount returns percentage of base', function () {
        $component = SalaryComponent::factory()->percentage(5)->create(['company_id' => $this->company->id]);

        expect($component->calculateAmount(10000000))->toBe(500000.0);
    });
});

describe('SalaryComponent Accessors', function () {
    test('type_label returns correct label for earning', function () {
        $component = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);

        expect($component->type_label)->toBe('Pendapatan');
    });

    test('type_label returns correct label for deduction', function () {
        $component = SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);

        expect($component->type_label)->toBe('Potongan');
    });

    test('category_label returns correct labels', function () {
        $categories = [
            'fixed' => 'Tetap',
            'variable' => 'Variabel',
            'benefit' => 'Tunjangan',
            'tax' => 'Pajak',
            'insurance' => 'Asuransi',
            'loan' => 'Pinjaman',
            'other' => 'Lainnya',
        ];

        foreach ($categories as $category => $label) {
            $component = SalaryComponent::factory()->create([
                'company_id' => $this->company->id,
                'category' => $category,
            ]);

            expect($component->category_label)->toBe($label);
        }
    });

    test('formatted_default_amount returns formatted currency', function () {
        $component = SalaryComponent::factory()->fixed(1500000)->create(['company_id' => $this->company->id]);

        expect($component->formatted_default_amount)->toBe('Rp 1.500.000');
    });
});

describe('SalaryComponent Soft Deletes', function () {
    test('can soft delete salary component', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        $component->delete();

        $this->assertSoftDeleted('salary_components', ['id' => $component->id]);
        expect(SalaryComponent::withTrashed()->find($component->id))->not->toBeNull();
    });

    test('can restore soft deleted salary component', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);
        $component->delete();

        $component->restore();

        expect(SalaryComponent::find($component->id))->not->toBeNull();
    });
});
