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
    // Create roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'hr-manager']);
    Role::create(['name' => 'employee']);

    // Create company and admin user
    $this->company = Company::factory()->create();
    $this->department = Department::factory()->create(['company_id' => $this->company->id]);
    $this->position = Position::factory()->create(['company_id' => $this->company->id]);

    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');
});

describe('Employee Index', function () {

    it('displays employee list page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/employees');

        $response->assertStatus(200);
        $response->assertViewIs('employees.index');
    });

    it('shows employees for current company only', function () {
        $this->actingAs($this->admin);

        // Create employees for this company
        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        // Create employees for another company
        $otherCompany = Company::factory()->create();
        $otherDept = Department::factory()->create(['company_id' => $otherCompany->id]);
        $otherPos = Position::factory()->create(['company_id' => $otherCompany->id]);
        Employee::factory()->count(2)->create([
            'company_id' => $otherCompany->id,
            'department_id' => $otherDept->id,
            'position_id' => $otherPos->id,
        ]);

        $response = $this->get('/employees');

        $response->assertStatus(200);
        $response->assertViewHas('employees', function ($employees) {
            return $employees->count() === 3;
        });
    });

    it('can filter employees by department', function () {
        $this->actingAs($this->admin);

        $dept1 = Department::factory()->create(['company_id' => $this->company->id, 'name' => 'HR']);
        $dept2 = Department::factory()->create(['company_id' => $this->company->id, 'name' => 'IT']);

        Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'department_id' => $dept1->id,
            'position_id' => $this->position->id,
        ]);
        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'department_id' => $dept2->id,
            'position_id' => $this->position->id,
        ]);

        $response = $this->get('/employees?department_id=' . $dept1->id);

        $response->assertStatus(200);
        $response->assertViewHas('employees', function ($employees) {
            return $employees->count() === 2;
        });
    });

    it('can search employees by name', function () {
        $this->actingAs($this->admin);

        Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response = $this->get('/employees?search=John');

        $response->assertStatus(200);
        $response->assertViewHas('employees', function ($employees) {
            return $employees->count() === 1 && $employees->first()->first_name === 'John';
        });
    });

    it('requires authentication', function () {
        $response = $this->get('/employees');

        $response->assertRedirect('/login');
    });

});

describe('Employee Create', function () {

    it('displays create employee form', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/employees/create');

        $response->assertStatus(200);
        $response->assertViewIs('employees.create');
        $response->assertViewHas('departments');
        $response->assertViewHas('positions');
    });

    it('can create a new employee', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@company.com',
            'phone' => '08123456789',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2024-01-15',
            'employment_status' => 'permanent',
            'gender' => 'male',
            'date_of_birth' => '1990-05-20',
            'base_salary' => 10000000,
        ]);

        $response->assertRedirect('/employees');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@company.com',
            'company_id' => $this->company->id,
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/employees', []);

        $response->assertSessionHasErrors([
            'first_name',
            'department_id',
            'position_id',
            'hire_date',
            'employment_status',
        ]);
    });

    it('validates email format', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/employees', [
            'first_name' => 'John',
            'email' => 'invalid-email',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2024-01-15',
            'employment_status' => 'permanent',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('auto-generates employee id', function () {
        $this->actingAs($this->admin);

        $this->post('/employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2024-01-15',
            'employment_status' => 'permanent',
        ]);

        $employee = Employee::first();
        expect($employee->employee_id)->toStartWith('EMP');
    });

});

describe('Employee Show', function () {

    it('displays employee details', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $response = $this->get("/employees/{$employee->id}");

        $response->assertStatus(200);
        $response->assertViewIs('employees.show');
        $response->assertViewHas('employee');
    });

    it('cannot view employee from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherDept = Department::factory()->create(['company_id' => $otherCompany->id]);
        $otherPos = Position::factory()->create(['company_id' => $otherCompany->id]);
        $employee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'department_id' => $otherDept->id,
            'position_id' => $otherPos->id,
        ]);

        $response = $this->get("/employees/{$employee->id}");

        $response->assertStatus(404);
    });

});

describe('Employee Edit', function () {

    it('displays edit employee form', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $response = $this->get("/employees/{$employee->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('employees.edit');
        $response->assertViewHas('employee');
        $response->assertViewHas('departments');
        $response->assertViewHas('positions');
    });

    it('can update employee', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'first_name' => 'John',
        ]);

        $response = $this->put("/employees/{$employee->id}", [
            'first_name' => 'Johnny',
            'last_name' => 'Updated',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => $employee->hire_date->format('Y-m-d'),
            'employment_status' => 'permanent',
        ]);

        $response->assertRedirect("/employees/{$employee->id}");
        $response->assertSessionHas('success');

        $employee->refresh();
        expect($employee->first_name)->toBe('Johnny')
            ->and($employee->last_name)->toBe('Updated');
    });

    it('cannot edit employee from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherDept = Department::factory()->create(['company_id' => $otherCompany->id]);
        $otherPos = Position::factory()->create(['company_id' => $otherCompany->id]);
        $employee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'department_id' => $otherDept->id,
            'position_id' => $otherPos->id,
        ]);

        $response = $this->get("/employees/{$employee->id}/edit");

        $response->assertStatus(404);
    });

});

describe('Employee Delete', function () {

    it('can delete employee', function () {
        $this->actingAs($this->admin);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ]);

        $response = $this->delete("/employees/{$employee->id}");

        $response->assertRedirect('/employees');
        $response->assertSessionHas('success');

        // Soft deleted
        expect(Employee::count())->toBe(0)
            ->and(Employee::withTrashed()->count())->toBe(1);
    });

    it('cannot delete employee from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherDept = Department::factory()->create(['company_id' => $otherCompany->id]);
        $otherPos = Position::factory()->create(['company_id' => $otherCompany->id]);
        $employee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
            'department_id' => $otherDept->id,
            'position_id' => $otherPos->id,
        ]);

        $response = $this->delete("/employees/{$employee->id}");

        $response->assertStatus(404);
        expect(Employee::count())->toBe(1);
    });

});
