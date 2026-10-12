<?php

use App\Models\Company;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('SalaryComponent Index', function () {
    test('can view salary components list', function () {
        $components = SalaryComponent::factory()->count(3)->create(['company_id' => $this->company->id]);

        $response = $this->get(route('salary-components.index'));

        $response->assertStatus(200);
        $response->assertViewIs('salary-components.index');
        $response->assertViewHas('salaryComponents');
    });

    test('only shows salary components from same company', function () {
        $ownComponent = SalaryComponent::factory()->create(['company_id' => $this->company->id]);
        $otherComponent = SalaryComponent::factory()->create();

        $response = $this->get(route('salary-components.index'));

        $response->assertStatus(200);
        $response->assertViewHas('salaryComponents', function ($components) use ($ownComponent, $otherComponent) {
            return $components->contains($ownComponent) && ! $components->contains($otherComponent);
        });
    });

    test('can search salary components by name', function () {
        SalaryComponent::factory()->create(['company_id' => $this->company->id, 'name' => 'Tunjangan Transport']);
        SalaryComponent::factory()->create(['company_id' => $this->company->id, 'name' => 'Tunjangan Makan']);

        $response = $this->get(route('salary-components.index', ['search' => 'Transport']));

        $response->assertStatus(200);
        $response->assertViewHas('salaryComponents', function ($components) {
            return $components->count() === 1 && $components->first()->name === 'Tunjangan Transport';
        });
    });

    test('can filter by type', function () {
        SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);
        SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('salary-components.index', ['type' => 'earning']));

        $response->assertStatus(200);
        $response->assertViewHas('salaryComponents', function ($components) {
            return $components->every(fn ($c) => $c->type === 'earning');
        });
    });

    test('can filter by active status', function () {
        SalaryComponent::factory()->create(['company_id' => $this->company->id, 'is_active' => true]);
        SalaryComponent::factory()->inactive()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('salary-components.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertViewHas('salaryComponents', function ($components) {
            return $components->every(fn ($c) => $c->is_active === true);
        });
    });
});

describe('SalaryComponent Create', function () {
    test('can view create form', function () {
        $response = $this->get(route('salary-components.create'));

        $response->assertStatus(200);
        $response->assertViewIs('salary-components.create');
    });

    test('can create earning component', function () {
        $data = [
            'name' => 'Tunjangan Transport',
            'code' => 'TT',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 500000,
            'description' => 'Tunjangan transportasi karyawan',
            'is_taxable' => true,
            'is_active' => true,
            'is_mandatory' => false,
            'sort_order' => 1,
        ];

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertRedirect(route('salary-components.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_components', [
            'company_id' => $this->company->id,
            'name' => 'Tunjangan Transport',
            'code' => 'TT',
            'type' => 'earning',
        ]);
    });

    test('can create deduction component', function () {
        $data = [
            'name' => 'BPJS Kesehatan',
            'code' => 'BPJSK',
            'type' => 'deduction',
            'category' => 'insurance',
            'calculation_type' => 'percentage',
            'percentage' => 1,
            'description' => 'Potongan BPJS Kesehatan',
            'is_taxable' => false,
            'is_active' => true,
            'is_mandatory' => true,
            'sort_order' => 1,
        ];

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertRedirect(route('salary-components.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_components', [
            'company_id' => $this->company->id,
            'name' => 'BPJS Kesehatan',
            'code' => 'BPJSK',
            'type' => 'deduction',
            'calculation_type' => 'percentage',
        ]);
    });

    test('name is required', function () {
        $data = SalaryComponent::factory()->make(['name' => ''])->toArray();

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertSessionHasErrors('name');
    });

    test('code must be unique within company', function () {
        SalaryComponent::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'TT',
        ]);

        $data = SalaryComponent::factory()->make(['code' => 'TT'])->toArray();

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertSessionHasErrors('code');
    });

    test('type must be valid', function () {
        $data = SalaryComponent::factory()->make(['type' => 'invalid'])->toArray();

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertSessionHasErrors('type');
    });

    test('calculation_type must be valid', function () {
        $data = SalaryComponent::factory()->make(['calculation_type' => 'invalid'])->toArray();

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertSessionHasErrors('calculation_type');
    });

    test('default_amount required when calculation_type is fixed', function () {
        $data = SalaryComponent::factory()->make([
            'calculation_type' => 'fixed',
            'default_amount' => null,
        ])->toArray();

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertSessionHasErrors('default_amount');
    });

    test('percentage required when calculation_type is percentage', function () {
        $data = SalaryComponent::factory()->make([
            'calculation_type' => 'percentage',
            'percentage' => null,
        ])->toArray();

        $response = $this->post(route('salary-components.store'), $data);

        $response->assertSessionHasErrors('percentage');
    });
});

describe('SalaryComponent Show', function () {
    test('can view salary component details', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('salary-components.show', $component));

        $response->assertStatus(200);
        $response->assertViewIs('salary-components.show');
        $response->assertViewHas('salaryComponent', $component);
    });

    test('cannot view salary component from other company', function () {
        $otherComponent = SalaryComponent::factory()->create();

        $response = $this->get(route('salary-components.show', $otherComponent));

        $response->assertStatus(404);
    });
});

describe('SalaryComponent Edit', function () {
    test('can view edit form', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        $response = $this->get(route('salary-components.edit', $component));

        $response->assertStatus(200);
        $response->assertViewIs('salary-components.edit');
        $response->assertViewHas('salaryComponent', $component);
    });

    test('can update salary component', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'name' => 'Tunjangan Transport Updated',
            'code' => 'TTU',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 750000,
            'is_taxable' => true,
            'is_active' => true,
            'is_mandatory' => false,
            'sort_order' => 1,
        ];

        $response = $this->put(route('salary-components.update', $component), $data);

        $response->assertRedirect(route('salary-components.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'name' => 'Tunjangan Transport Updated',
            'code' => 'TTU',
        ]);
    });

    test('cannot update salary component from other company', function () {
        $otherComponent = SalaryComponent::factory()->create();

        $response = $this->put(route('salary-components.update', $otherComponent), [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(404);
    });

    test('code unique validation ignores current record', function () {
        $component = SalaryComponent::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'TT',
        ]);

        $data = [
            'name' => 'Updated Name',
            'code' => 'TT',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 500000,
            'is_taxable' => true,
            'is_active' => true,
            'is_mandatory' => false,
            'sort_order' => 1,
        ];

        $response = $this->put(route('salary-components.update', $component), $data);

        $response->assertRedirect(route('salary-components.index'));
        $response->assertSessionHas('success');
    });
});

describe('SalaryComponent Delete', function () {
    test('can delete salary component', function () {
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        $response = $this->delete(route('salary-components.destroy', $component));

        $response->assertRedirect(route('salary-components.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('salary_components', ['id' => $component->id]);
    });

    test('cannot delete salary component from other company', function () {
        $otherComponent = SalaryComponent::factory()->create();

        $response = $this->delete(route('salary-components.destroy', $otherComponent));

        $response->assertStatus(404);
    });
});

describe('SalaryComponent Toggle Status', function () {
    test('can toggle salary component status', function () {
        $component = SalaryComponent::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->patch(route('salary-components.toggle-status', $component));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('salary_components', [
            'id' => $component->id,
            'is_active' => false,
        ]);
    });

    test('cannot toggle status of salary component from other company', function () {
        $otherComponent = SalaryComponent::factory()->create();

        $response = $this->patch(route('salary-components.toggle-status', $otherComponent));

        $response->assertStatus(404);
    });
});
