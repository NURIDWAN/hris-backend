<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('OfficeLocation Index', function () {
    it('displays office locations list', function () {
        OfficeLocation::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('office-locations.index'));

        $response->assertOk()
            ->assertViewIs('office-locations.index')
            ->assertViewHas('officeLocations');
    });

    it('only shows office locations for current company', function () {
        $otherCompany = Company::factory()->create();

        OfficeLocation::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);
        OfficeLocation::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->get(route('office-locations.index'));

        $response->assertOk();
        expect($response->viewData('officeLocations'))->toHaveCount(2);
    });

    it('can search office locations by name', function () {
        OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Jakarta Office',
        ]);
        OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Bandung Office',
        ]);

        $response = $this->get(route('office-locations.index', ['search' => 'Jakarta']));

        $response->assertOk();
        expect($response->viewData('officeLocations'))->toHaveCount(1);
    });
});

describe('OfficeLocation Create', function () {
    it('displays create form', function () {
        $response = $this->get(route('office-locations.create'));

        $response->assertOk()
            ->assertViewIs('office-locations.create');
    });
});

describe('OfficeLocation Store', function () {
    it('creates a new office location', function () {
        $data = [
            'name' => 'Jakarta Headquarters',
            'code' => 'JKT-HQ',
            'address' => 'Jl. Sudirman No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 100,
            'is_active' => true,
            'is_headquarters' => true,
        ];

        $response = $this->post(route('office-locations.store'), $data);

        $response->assertRedirect(route('office-locations.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('office_locations', [
            'company_id' => $this->company->id,
            'name' => 'Jakarta Headquarters',
            'code' => 'JKT-HQ',
        ]);
    });

    it('validates required fields', function () {
        $response = $this->post(route('office-locations.store'), []);

        $response->assertSessionHasErrors(['name', 'code']);
    });

    it('validates unique code per company', function () {
        OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'HQ',
        ]);

        $response = $this->post(route('office-locations.store'), [
            'name' => 'Another HQ',
            'code' => 'HQ',
        ]);

        $response->assertSessionHasErrors(['code']);
    });

    it('validates latitude range', function () {
        $response = $this->post(route('office-locations.store'), [
            'name' => 'Test Office',
            'code' => 'TST',
            'latitude' => 100, // Invalid: must be between -90 and 90
        ]);

        $response->assertSessionHasErrors(['latitude']);
    });

    it('validates longitude range', function () {
        $response = $this->post(route('office-locations.store'), [
            'name' => 'Test Office',
            'code' => 'TST',
            'longitude' => 200, // Invalid: must be between -180 and 180
        ]);

        $response->assertSessionHasErrors(['longitude']);
    });
});

describe('OfficeLocation Edit', function () {
    it('displays edit form', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('office-locations.edit', $officeLocation));

        $response->assertOk()
            ->assertViewIs('office-locations.edit')
            ->assertViewHas('officeLocation');
    });

    it('returns 404 for other company office location', function () {
        $otherCompany = Company::factory()->create();
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->get(route('office-locations.edit', $officeLocation));

        $response->assertNotFound();
    });
});

describe('OfficeLocation Update', function () {
    it('updates an office location', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Old Name',
        ]);

        $response = $this->put(route('office-locations.update', $officeLocation), [
            'name' => 'New Name',
            'code' => $officeLocation->code,
            'latitude' => $officeLocation->latitude,
            'longitude' => $officeLocation->longitude,
            'radius' => $officeLocation->radius,
        ]);

        $response->assertRedirect(route('office-locations.index'))
            ->assertSessionHas('success');

        expect($officeLocation->fresh()->name)->toBe('New Name');
    });

    it('validates unique code excluding self', function () {
        $officeLocation1 = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'HQ1',
        ]);
        $officeLocation2 = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'code' => 'HQ2',
        ]);

        // Updating officeLocation2 with officeLocation1's code should fail
        $response = $this->put(route('office-locations.update', $officeLocation2), [
            'name' => 'Test',
            'code' => 'HQ1',
        ]);

        $response->assertSessionHasErrors(['code']);
    });
});

describe('OfficeLocation Delete', function () {
    it('deletes an office location', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete(route('office-locations.destroy', $officeLocation));

        $response->assertRedirect(route('office-locations.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('office_locations', [
            'id' => $officeLocation->id,
        ]);
    });

    it('cannot delete office location from other company', function () {
        $otherCompany = Company::factory()->create();
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->delete(route('office-locations.destroy', $officeLocation));

        $response->assertNotFound();
    });
});

describe('OfficeLocation Employee Assignment', function () {
    it('displays show page with assigned employees', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employees = Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);

        $officeLocation->employees()->attach($employees->pluck('id'));

        $response = $this->get(route('office-locations.show', $officeLocation));

        $response->assertOk()
            ->assertViewIs('office-locations.show')
            ->assertViewHas('officeLocation')
            ->assertViewHas('assignedEmployees');
    });

    it('can assign employees to office location', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employees = Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post(route('office-locations.assign-employees', $officeLocation), [
            'employee_ids' => $employees->pluck('id')->toArray(),
        ]);

        $response->assertRedirect()
            ->assertSessionHas('success');

        expect($officeLocation->fresh()->employees)->toHaveCount(2);
    });

    it('can remove employee from office location', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $officeLocation->employees()->attach($employee->id);

        $response = $this->delete(route('office-locations.remove-employee', [
            'officeLocation' => $officeLocation,
            'employee' => $employee,
        ]));

        $response->assertRedirect()
            ->assertSessionHas('success');

        expect($officeLocation->fresh()->employees)->toHaveCount(0);
    });

    it('can set primary office for employee', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post(route('office-locations.assign-employees', $officeLocation), [
            'employee_ids' => [$employee->id],
            'primary_employee_id' => $employee->id,
        ]);

        $response->assertRedirect();

        $pivot = $officeLocation->employees()->where('employees.id', $employee->id)->first()->pivot;
        expect($pivot->is_primary)->toBeTrue();
    });
});

describe('OfficeLocation Toggle Status', function () {
    it('can toggle office location active status', function () {
        $officeLocation = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->patch(route('office-locations.toggle-status', $officeLocation));

        $response->assertRedirect();
        expect($officeLocation->fresh()->is_active)->toBeFalse();
    });
});
