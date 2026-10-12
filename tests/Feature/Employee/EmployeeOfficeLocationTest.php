<?php

/**
 * Tests for Employee Office Location Assignment.
 *
 * - Create employee with office locations
 * - Edit employee office locations
 * - Set primary office location
 */

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();

    Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');

    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->position = Position::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->officeA = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Pusat',
        'code' => 'HQ-001',
    ]);

    $this->officeB = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Cabang',
        'code' => 'BR-001',
    ]);
});

describe('Create Employee with Office Locations', function () {
    it('shows office locations on create form', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.create'));

        $response->assertOk()
            ->assertSee('Lokasi Kantor')
            ->assertSee('Kantor Pusat')
            ->assertSee('HQ-001')
            ->assertSee('Kantor Cabang')
            ->assertSee('BR-001');
    });

    it('creates employee with single office location', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.store'), [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => now()->format('Y-m-d'),
                'employment_status' => 'permanent',
                'office_location_ids' => [$this->officeA->id],
                'primary_office_id' => $this->officeA->id,
            ]);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::where('first_name', 'John')->first();
        expect($employee)->not->toBeNull();
        expect($employee->officeLocations)->toHaveCount(1);
        expect($employee->officeLocations->first()->id)->toBe($this->officeA->id);
        expect((bool) $employee->officeLocations->first()->pivot->is_primary)->toBeTrue();
    });

    it('creates employee with multiple office locations', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.store'), [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => now()->format('Y-m-d'),
                'employment_status' => 'permanent',
                'office_location_ids' => [$this->officeA->id, $this->officeB->id],
                'primary_office_id' => $this->officeB->id,
            ]);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::where('first_name', 'Jane')->first();
        expect($employee)->not->toBeNull();
        expect($employee->officeLocations)->toHaveCount(2);

        $primaryOffice = $employee->officeLocations->where('pivot.is_primary', true)->first();
        expect($primaryOffice->id)->toBe($this->officeB->id);
    });

    it('creates employee without office locations', function () {
        $response = $this->actingAs($this->admin)
            ->post(route('employees.store'), [
                'first_name' => 'Bob',
                'last_name' => 'Wilson',
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => now()->format('Y-m-d'),
                'employment_status' => 'permanent',
            ]);

        $response->assertRedirect(route('employees.index'));

        $employee = Employee::where('first_name', 'Bob')->first();
        expect($employee)->not->toBeNull();
        expect($employee->officeLocations)->toHaveCount(0);
    });
});

describe('Edit Employee Office Locations', function () {
    beforeEach(function () {
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);
    });

    it('shows office locations on edit form', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('employees.edit', $this->employee));

        $response->assertOk()
            ->assertSee('Lokasi Kantor')
            ->assertSee('Kantor Pusat')
            ->assertSee('Kantor Cabang');
    });

    it('shows currently assigned offices as checked', function () {
        $this->employee->officeLocations()->attach([
            $this->officeA->id => ['is_primary' => true],
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('employees.edit', $this->employee));

        $response->assertOk();
        // The office should be in the selectedOffices array (check for the ID in the array)
        $response->assertSee('selectedOffices:');
        $response->assertSee((string) $this->officeA->id);
    });

    it('updates employee office locations', function () {
        $this->employee->officeLocations()->attach([
            $this->officeA->id => ['is_primary' => true],
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('employees.update', $this->employee), [
                'first_name' => $this->employee->first_name,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => $this->employee->hire_date->format('Y-m-d'),
                'employment_status' => $this->employee->employment_status,
                'office_location_ids' => [$this->officeB->id],
                'primary_office_id' => $this->officeB->id,
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));

        $this->employee->refresh();
        expect($this->employee->officeLocations)->toHaveCount(1);
        expect($this->employee->officeLocations->first()->id)->toBe($this->officeB->id);
    });

    it('adds office location to existing employee', function () {
        $response = $this->actingAs($this->admin)
            ->put(route('employees.update', $this->employee), [
                'first_name' => $this->employee->first_name,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => $this->employee->hire_date->format('Y-m-d'),
                'employment_status' => $this->employee->employment_status,
                'office_location_ids' => [$this->officeA->id, $this->officeB->id],
                'primary_office_id' => $this->officeA->id,
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));

        $this->employee->refresh();
        expect($this->employee->officeLocations)->toHaveCount(2);
    });

    it('removes all office locations', function () {
        $this->employee->officeLocations()->attach([
            $this->officeA->id => ['is_primary' => true],
            $this->officeB->id => ['is_primary' => false],
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('employees.update', $this->employee), [
                'first_name' => $this->employee->first_name,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => $this->employee->hire_date->format('Y-m-d'),
                'employment_status' => $this->employee->employment_status,
                'office_location_ids' => [],
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));

        $this->employee->refresh();
        expect($this->employee->officeLocations)->toHaveCount(0);
    });

    it('changes primary office location', function () {
        $this->employee->officeLocations()->attach([
            $this->officeA->id => ['is_primary' => true],
            $this->officeB->id => ['is_primary' => false],
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('employees.update', $this->employee), [
                'first_name' => $this->employee->first_name,
                'department_id' => $this->department->id,
                'position_id' => $this->position->id,
                'hire_date' => $this->employee->hire_date->format('Y-m-d'),
                'employment_status' => $this->employee->employment_status,
                'office_location_ids' => [$this->officeA->id, $this->officeB->id],
                'primary_office_id' => $this->officeB->id,
            ]);

        $response->assertRedirect(route('employees.show', $this->employee));

        $this->employee->refresh();
        $primaryOffice = $this->employee->officeLocations->where('pivot.is_primary', true)->first();
        expect($primaryOffice->id)->toBe($this->officeB->id);

        $secondaryOffice = $this->employee->officeLocations->where('pivot.is_primary', false)->first();
        expect($secondaryOffice->id)->toBe($this->officeA->id);
    });
});
