<?php

use App\Models\Company;
use App\Models\Spt1721;
use App\Models\Spt1721Monthly;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);

    // Setup roles for the company
    createStandardRoles($this->company->id);
    $this->user->assignRole('admin');

    $this->actingAs($this->user);
});

describe('SPT 1721 Index', function () {
    test('can view SPT list', function () {
        createSptWithMonthlyData($this->company, 2026);
        createSptWithMonthlyData($this->company, 2025);

        $response = $this->get(route('spt-1721.index'));

        $response->assertStatus(200);
        $response->assertViewIs('spt-1721.index');
        $response->assertViewHas('spts');
    });

    test('only shows SPT from same company', function () {
        $ownSpt = createSptWithMonthlyData($this->company, 2026);
        $otherSpt = createSptWithMonthlyData(Company::factory()->create(), 2026);

        $response = $this->get(route('spt-1721.index'));

        $response->assertStatus(200);
        $response->assertViewHas('spts', function ($spts) use ($ownSpt, $otherSpt) {
            return $spts->contains($ownSpt) && ! $spts->contains($otherSpt);
        });
    });

    test('can filter by year', function () {
        createSptWithMonthlyData($this->company, 2026);
        createSptWithMonthlyData($this->company, 2025);

        $response = $this->get(route('spt-1721.index', ['year' => 2026]));

        $response->assertStatus(200);
        $response->assertViewHas('spts', function ($spts) {
            return $spts->every(fn ($s) => $s->tax_year === 2026);
        });
    });

    test('can filter by status', function () {
        createSptWithMonthlyData($this->company, 2026, 'draft');
        createSptWithMonthlyData($this->company, 2025, 'reported');

        $response = $this->get(route('spt-1721.index', ['status' => 'draft']));

        $response->assertStatus(200);
        $response->assertViewHas('spts', function ($spts) {
            return $spts->every(fn ($s) => $s->status === 'draft');
        });
    });
});

describe('SPT 1721 Create', function () {
    test('can view create form', function () {
        $response = $this->get(route('spt-1721.create'));

        $response->assertStatus(200);
        $response->assertViewIs('spt-1721.create');
        $response->assertViewHas('availableYears');
    });

    test('can create SPT', function () {
        $response = $this->post(route('spt-1721.store'), [
            'tax_year' => 2026,
        ]);

        $response->assertRedirect();
        expect(Spt1721::where('company_id', $this->company->id)->where('tax_year', 2026)->exists())->toBeTrue();
    });

    test('cannot create duplicate SPT for same year', function () {
        createSptWithMonthlyData($this->company, 2026);

        $response = $this->post(route('spt-1721.store'), [
            'tax_year' => 2026,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('SPT 1721 Show', function () {
    test('can view SPT detail', function () {
        $spt = createSptWithMonthlyData($this->company, 2026);

        $response = $this->get(route('spt-1721.show', $spt));

        $response->assertStatus(200);
        $response->assertViewIs('spt-1721.show');
        $response->assertViewHas('spt1721');
    });

    test('cannot view SPT from other company', function () {
        $otherSpt = createSptWithMonthlyData(Company::factory()->create(), 2026);

        $response = $this->get(route('spt-1721.show', $otherSpt));

        $response->assertStatus(404);
    });
});

describe('SPT 1721 Calculate', function () {
    test('can calculate SPT from payroll', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'draft');

        $response = $this->post(route('spt-1721.calculate', $spt));

        $response->assertRedirect();
        $spt->refresh();
        expect($spt->status)->toBe('calculated');
    });

    test('cannot calculate non-draft SPT', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'submitted');

        $response = $this->post(route('spt-1721.calculate', $spt));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('SPT 1721 Submit', function () {
    test('can submit SPT with NTPN', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'calculated');

        $response = $this->post(route('spt-1721.submit', $spt), [
            'ntpn' => 'ABCD1234EFGH5678',
        ]);

        $response->assertRedirect();
        $spt->refresh();
        expect($spt->status)->toBe('submitted');
        expect($spt->ntpn)->toBe('ABCD1234EFGH5678');
    });

    test('cannot submit SPT without NTPN', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'calculated');

        $response = $this->post(route('spt-1721.submit', $spt), []);

        $response->assertSessionHasErrors('ntpn');
    });

    test('cannot submit draft SPT', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'draft');

        $response = $this->post(route('spt-1721.submit', $spt), [
            'ntpn' => 'ABCD1234EFGH5678',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('SPT 1721 Report', function () {
    test('can report submitted SPT', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'submitted');

        $response = $this->post(route('spt-1721.report', $spt), [
            'submission_receipt' => 'BPE-123456789-2026',
        ]);

        $response->assertRedirect();
        $spt->refresh();
        expect($spt->status)->toBe('reported');
        expect($spt->submission_receipt)->toBe('BPE-123456789-2026');
    });

    test('cannot report non-submitted SPT', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'draft');

        $response = $this->post(route('spt-1721.report', $spt), [
            'submission_receipt' => 'BPE-123456789-2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('SPT 1721 Pembetulan', function () {
    test('can create pembetulan', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'reported');

        $response = $this->post(route('spt-1721.create-pembetulan', $spt));

        $response->assertRedirect();
        expect(Spt1721::where('company_id', $this->company->id)
            ->where('tax_year', 2026)
            ->where('spt_type', 'pembetulan')
            ->exists())->toBeTrue();
    });
});

describe('SPT 1721 Delete', function () {
    test('can delete draft SPT', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'draft');

        $response = $this->delete(route('spt-1721.destroy', $spt));

        $response->assertRedirect(route('spt-1721.index'));
        expect(Spt1721::withTrashed()->find($spt->id)->trashed())->toBeTrue();
    });

    test('cannot delete non-draft SPT', function () {
        $spt = createSptWithMonthlyData($this->company, 2026, 'submitted');

        $response = $this->delete(route('spt-1721.destroy', $spt));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('SPT 1721 Export', function () {
    test('can export induk CSV', function () {
        $spt = createSptWithMonthlyData($this->company, 2026);

        $response = $this->get(route('spt-1721.export', [$spt, 'induk']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    test('can export lampiran_i CSV', function () {
        $spt = createSptWithMonthlyData($this->company, 2026);

        $response = $this->get(route('spt-1721.export', [$spt, 'lampiran_i']));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    });

    test('returns 404 for invalid export type', function () {
        $spt = createSptWithMonthlyData($this->company, 2026);

        $response = $this->get(route('spt-1721.export', [$spt, 'invalid']));

        $response->assertStatus(404);
    });
});

/**
 * Helper function to create SPT with monthly data
 */
function createSptWithMonthlyData(Company $company, int $year, string $status = 'draft'): Spt1721
{
    $spt = Spt1721::create([
        'company_id' => $company->id,
        'tax_year' => $year,
        'spt_type' => 'normal',
        'pembetulan_ke' => 0,
        'company_npwp' => '01.234.567.8-012.000',
        'company_name' => $company->name,
        'company_address' => 'Test Address',
        'signer_name' => 'Direktur',
        'signer_position' => 'Direktur',
        'status' => $status,
    ]);

    // Create 12 monthly data
    for ($month = 1; $month <= 12; $month++) {
        Spt1721Monthly::create([
            'spt_1721_id' => $spt->id,
            'month' => $month,
            'employee_count' => 10,
            'bruto_pegawai_tetap' => 100000000,
            'pph21_pegawai_tetap' => 5000000,
            'total_bruto' => 100000000,
            'total_pph21' => 5000000,
            'pph21_disetor' => 5000000,
        ]);
    }

    return $spt;
}
