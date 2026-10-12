# Testing Standards

## Framework

- **Pest PHP 3** (NOT PHPUnit)
- Create tests: `php artisan make:test --pest {name}`
- Run tests: `php artisan test --compact`
- Filter: `php artisan test --compact --filter=testName`

## TDD Approach (MANDATORY)

1. **Red**: Write test first (expect failure)
2. **Green**: Write minimum code to pass
3. **Refactor**: Clean up without changing behavior

## Test Structure

```php
<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->user->assignRole('admin');
});

describe('Employee Management', function () {
    it('can list employees', function () {
        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.index'));

        $response->assertOk()
            ->assertViewHas('employees');
    });

    it('can create an employee', function () {
        $data = [
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            // ...
        ];

        $response = $this->actingAs($this->user)
            ->post(route('employees.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', [
            'company_id' => $this->company->id,
            'full_name' => 'John Doe',
        ]);
    });

    it('cannot access other tenant data', function () {
        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('employees.show', $otherEmployee));

        $response->assertNotFound();
    });
});
```

## Test Categories

### 1. Tenant Isolation (CRITICAL)
Every feature test MUST verify that data from other tenants is inaccessible.

### 2. CRUD Operations
Test create, read, update, delete for each resource.

### 3. Authorization
Test that users without proper roles/permissions are denied access.

### 4. Payroll Calculations
Test salary computation, tax calculations, BPJS deductions, THR.

### 5. Attendance Logic
Test clock in/out, late detection, overtime calculation.

### 6. Leave Management
Test balance deduction, approval workflow, cancellation.

### 7. Approval Workflows
Test multi-step approval processes.

### 8. Form Validation
Test all validation rules with invalid data.

## Test Naming

```php
it('can create an employee with valid data', function () { });
it('cannot create employee without required fields', function () { });
it('prevents access to other tenant employees', function () { });
it('calculates PPh21 correctly for TER method', function () { });
```

## Factories

Always use factories for test data. Check for existing states before manually setting up:

```php
// Use factory states
$employee = Employee::factory()->active()->create();
$payroll = Payroll::factory()->draft()->create();

// Use fake() for random data
$name = fake()->name();
$email = fake()->safeEmail();
```

## Run Commands

```bash
# All tests
php artisan test --compact

# Specific file
php artisan test --compact tests/Feature/Employee/EmployeeControllerTest.php

# Filter by name
php artisan test --compact --filter="can create employee"

# With coverage
php artisan test --compact --coverage
```

## Best Practices

1. Use `RefreshDatabase` trait in all tests
2. Use factories with states — avoid manual model creation
3. Test tenant isolation in EVERY feature test
4. Test both success and failure scenarios
5. Test form validation rules
6. Test authorization (role/permission checks)
7. Use `assertDatabaseHas` / `assertDatabaseMissing` for data verification
8. Keep tests focused — one assertion concept per test
9. Run `vendor/bin/pint --dirty` before committing test files
10. Follow existing test patterns — check sibling test files
