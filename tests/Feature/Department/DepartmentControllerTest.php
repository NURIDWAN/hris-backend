<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
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
});

describe('DepartmentController', function () {
    describe('index', function () {
        it('displays list of departments', function () {
            $departments = Department::factory()->count(3)->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('departments.index'));

            $response->assertOk();
            $response->assertViewIs('departments.index');
            $response->assertViewHas('departments');
        });

        it('only shows departments belonging to current company', function () {
            $myDepartment = Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'My Department',
            ]);

            $otherCompany = Company::factory()->create();
            $otherDepartment = Department::factory()->create([
                'company_id' => $otherCompany->id,
                'name' => 'Other Department',
            ]);

            $response = $this->get(route('departments.index'));

            $response->assertOk();
            $response->assertSee('My Department');
            $response->assertDontSee('Other Department');
        });

        it('can search departments by name', function () {
            Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Engineering',
            ]);
            Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Marketing',
            ]);

            $response = $this->get(route('departments.index', ['search' => 'Engineer']));

            $response->assertOk();
            $response->assertSee('Engineering');
            $response->assertDontSee('Marketing');
        });
    });

    describe('create', function () {
        it('displays create department form', function () {
            $response = $this->get(route('departments.create'));

            $response->assertOk();
            $response->assertViewIs('departments.create');
        });
    });

    describe('store', function () {
        it('creates a new department', function () {
            $data = [
                'name' => 'Engineering',
                'code' => 'ENG',
                'description' => 'Engineering Department',
                'is_active' => true,
            ];

            $response = $this->post(route('departments.store'), $data);

            $response->assertRedirect(route('departments.index'));
            $this->assertDatabaseHas('departments', [
                'company_id' => $this->company->id,
                'name' => 'Engineering',
                'code' => 'ENG',
            ]);
        });

        it('validates required fields', function () {
            $response = $this->post(route('departments.store'), []);

            $response->assertSessionHasErrors(['name']);
        });

        it('validates unique code within company', function () {
            Department::factory()->create([
                'company_id' => $this->company->id,
                'code' => 'ENG',
            ]);

            $response = $this->post(route('departments.store'), [
                'name' => 'New Department',
                'code' => 'ENG',
            ]);

            $response->assertSessionHasErrors(['code']);
        });
    });

    describe('edit', function () {
        it('displays edit department form', function () {
            $department = Department::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('departments.edit', $department));

            $response->assertOk();
            $response->assertViewIs('departments.edit');
            $response->assertViewHas('department');
        });

        it('returns 404 for department from another company', function () {
            $otherCompany = Company::factory()->create();
            $department = Department::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->get(route('departments.edit', $department));

            $response->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates department', function () {
            $department = Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Old Name',
            ]);

            $response = $this->put(route('departments.update', $department), [
                'name' => 'New Name',
                'code' => 'NEW',
                'is_active' => true,
            ]);

            $response->assertRedirect(route('departments.index'));
            $this->assertDatabaseHas('departments', [
                'id' => $department->id,
                'name' => 'New Name',
            ]);
        });
    });

    describe('destroy', function () {
        it('deletes department without employees', function () {
            $department = Department::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->delete(route('departments.destroy', $department));

            $response->assertRedirect(route('departments.index'));
            $this->assertSoftDeleted('departments', ['id' => $department->id]);
        });

        it('cannot delete department with employees', function () {
            $department = Department::factory()->create([
                'company_id' => $this->company->id,
            ]);

            Employee::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $department->id,
            ]);

            $response = $this->delete(route('departments.destroy', $department));

            $response->assertRedirect();
            $response->assertSessionHas('error');
            $this->assertDatabaseHas('departments', ['id' => $department->id]);
        });
    });
});
