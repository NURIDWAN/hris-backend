<?php

use App\Models\Company;
use App\Models\Pph21TerRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');
});

describe('TER Rate Initialization', function () {
    it('initializes ter rates for a year', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-ter-rates'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        $count = Pph21TerRate::where('company_id', $this->company->id)
            ->where('year', 2026)
            ->count();

        expect($count)->toBeGreaterThan(100);

        // Verify category A exists
        $this->assertDatabaseHas('pph21_ter_rates', [
            'company_id' => $this->company->id,
            'category' => 'A',
            'min_income' => 0,
            'rate' => 0,
            'year' => 2026,
        ]);

        // Verify category B exists
        $this->assertDatabaseHas('pph21_ter_rates', [
            'company_id' => $this->company->id,
            'category' => 'B',
            'min_income' => 0,
            'rate' => 0,
            'year' => 2026,
        ]);

        // Verify category C exists
        $this->assertDatabaseHas('pph21_ter_rates', [
            'company_id' => $this->company->id,
            'category' => 'C',
            'min_income' => 0,
            'rate' => 0,
            'year' => 2026,
        ]);
    });

    it('prevents duplicate initialization for same year', function () {
        Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
            'year' => 2026,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-ter-rates'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('error');
    });

    it('validates year range', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-ter-rates'), [
            'year' => 2019,
        ]);

        $response->assertSessionHasErrors(['year']);
    });
});

describe('TER Rate Update', function () {
    it('updates ter rate', function () {
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
            'category' => 'A',
            'rate' => 1.50,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ter-rate', $terRate), [
            'rate' => 2.00,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        $terRate->refresh();
        expect((float) $terRate->rate)->toEqual(2.00);
    });

    it('validates rate is required', function () {
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ter-rate', $terRate), []);

        $response->assertSessionHasErrors(['rate']);
    });

    it('validates rate range', function () {
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ter-rate', $terRate), [
            'rate' => 150,
        ]);

        $response->assertSessionHasErrors(['rate']);
    });

    it('prevents updating other company ter rate', function () {
        $otherCompany = Company::factory()->create();
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ter-rate', $terRate), [
            'rate' => 5.00,
        ]);

        $response->assertStatus(403);
    });
});

describe('TER Rate Model', function () {
    it('returns correct category label', function () {
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
            'category' => 'A',
        ]);

        expect($terRate->category_label)->toBe('Kategori A (TK/0, TK/1, K/0)');
    });

    it('returns correct range label for bounded range', function () {
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
            'min_income' => 5400000,
            'max_income' => 5650000,
        ]);

        expect($terRate->range_label)->toBe('Rp 5.400.000 - Rp 5.650.000');
    });

    it('returns correct range label for unbounded max', function () {
        $terRate = Pph21TerRate::factory()->create([
            'company_id' => $this->company->id,
            'min_income' => 1400000000,
            'max_income' => null,
        ]);

        expect($terRate->range_label)->toBe('Di atas Rp 1.400.000.000');
    });

    it('has correct default rates count', function () {
        $defaults = Pph21TerRate::getDefaultTerRates2024();

        expect(count($defaults))->toBeGreaterThan(100);

        $categories = collect($defaults)->groupBy('category');
        expect($categories->keys()->sort()->values()->toArray())->toBe(['A', 'B', 'C']);
    });
});

describe('TER Rates on Settings Page', function () {
    it('displays ter rates grouped by category on settings page', function () {
        // Initialize TER rates
        $defaults = Pph21TerRate::getDefaultTerRates2024();
        $year = date('Y');

        foreach (array_slice($defaults, 0, 3) as $rate) {
            Pph21TerRate::factory()->create([
                'company_id' => $this->company->id,
                'category' => $rate['category'],
                'min_income' => $rate['min_income'],
                'max_income' => $rate['max_income'],
                'rate' => $rate['rate'],
                'year' => $year,
            ]);
        }

        $this->actingAs($this->admin);

        $response = $this->get(route('pph21-settings.index'));

        $response->assertStatus(200);
        $response->assertViewHas('terRates');
    });
});
