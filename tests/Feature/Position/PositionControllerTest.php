<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->role = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);

    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('PositionController', function () {
    describe('index', function () {
        it('displays list of positions', function () {
            Position::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
            ]);

            $response = $this->get(route('positions.index'));

            $response->assertOk();
            $response->assertViewIs('positions.index');
            $response->assertViewHas('positions');
        });

        it('only shows positions belonging to current company', function () {
            $myPosition = Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'name' => 'My Position',
            ]);

            $otherCompany = Company::factory()->create();
            $otherDepartment = Department::factory()->create([
                'company_id' => $otherCompany->id,
            ]);
            Position::factory()->create([
                'company_id' => $otherCompany->id,
                'department_id' => $otherDepartment->id,
                'name' => 'Other Position',
            ]);

            $response = $this->get(route('positions.index'));

            $response->assertOk();
            $response->assertSee('My Position');
            $response->assertDontSee('Other Position');
        });

        it('can filter positions by department', function () {
            $dept1 = Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Engineering',
            ]);
            $dept2 = Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Marketing',
            ]);

            Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $dept1->id,
                'name' => 'Software Engineer',
            ]);
            Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $dept2->id,
                'name' => 'Marketing Manager',
            ]);

            $response = $this->get(route('positions.index', ['department_id' => $dept1->id]));

            $response->assertOk();
            $response->assertSee('Software Engineer');
            $response->assertDontSee('Marketing Manager');
        });
    });

    describe('create', function () {
        it('displays create position form', function () {
            $response = $this->get(route('positions.create'));

            $response->assertOk();
            $response->assertViewIs('positions.create');
            $response->assertViewHas('departments');
        });
    });

    describe('store', function () {
        it('creates a new position', function () {
            $data = [
                'name' => 'Software Engineer',
                'code' => 'SE',
                'department_id' => $this->department->id,
                'level' => 3,
                'base_salary' => 10000000,
                'is_active' => true,
            ];

            $response = $this->post(route('positions.store'), $data);

            $response->assertRedirect(route('positions.index'));
            $this->assertDatabaseHas('positions', [
                'company_id' => $this->company->id,
                'name' => 'Software Engineer',
                'code' => 'SE',
            ]);
        });

        it('validates required fields', function () {
            $response = $this->post(route('positions.store'), []);

            $response->assertSessionHasErrors(['name', 'department_id']);
        });

        it('validates unique code within company', function () {
            Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'code' => 'SE',
            ]);

            $response = $this->post(route('positions.store'), [
                'name' => 'New Position',
                'code' => 'SE',
                'department_id' => $this->department->id,
            ]);

            $response->assertSessionHasErrors(['code']);
        });
    });

    describe('edit', function () {
        it('displays edit position form', function () {
            $position = Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
            ]);

            $response = $this->get(route('positions.edit', $position));

            $response->assertOk();
            $response->assertViewIs('positions.edit');
            $response->assertViewHas('position');
        });

        it('returns 404 for position from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherDepartment = Department::factory()->create([
                'company_id' => $otherCompany->id,
            ]);
            $position = Position::factory()->create([
                'company_id' => $otherCompany->id,
                'department_id' => $otherDepartment->id,
            ]);

            $response = $this->get(route('positions.edit', $position));

            $response->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates position', function () {
            $position = Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'name' => 'Old Name',
            ]);

            $response = $this->put(route('positions.update', $position), [
                'name' => 'New Name',
                'code' => 'NEW',
                'department_id' => $this->department->id,
                'is_active' => true,
            ]);

            $response->assertRedirect(route('positions.index'));
            $this->assertDatabaseHas('positions', [
                'id' => $position->id,
                'name' => 'New Name',
            ]);
        });
    });

    describe('destroy', function () {
        it('deletes position without employees', function () {
            $position = Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
            ]);

            $response = $this->delete(route('positions.destroy', $position));

            $response->assertRedirect(route('positions.index'));
            $this->assertSoftDeleted('positions', ['id' => $position->id]);
        });

        it('cannot delete position with employees', function () {
            $position = Position::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
            ]);

            Employee::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'position_id' => $position->id,
            ]);

            $response = $this->delete(route('positions.destroy', $position));

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('positions', ['id' => $position->id]);
        });
    });
});
