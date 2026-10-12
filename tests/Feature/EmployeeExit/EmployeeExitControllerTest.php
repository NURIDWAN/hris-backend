<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeExit;
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

describe('EmployeeExitController', function () {
    describe('index', function () {
        it('displays employee exit list page', function () {
            $response = $this->get(route('employee-exits.index'));

            $response->assertOk();
            $response->assertViewIs('employee-exits.index');
        });

        it('shows exits for current company only', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit1 = EmployeeExit::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);
            $exit2 = EmployeeExit::factory()->create([
                'company_id' => $otherCompany->id,
                'employee_id' => $otherEmployee->id,
            ]);

            $response = $this->get(route('employee-exits.index'));

            $response->assertOk();
            $response->assertSee($employee->full_name);
            $response->assertDontSee($otherEmployee->full_name);
        });

        it('can filter by status', function () {
            $employee1 = Employee::factory()->create(['company_id' => $this->company->id]);
            $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);

            EmployeeExit::factory()->pending()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee1->id,
            ]);
            EmployeeExit::factory()->completed()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee2->id,
            ]);

            $response = $this->get(route('employee-exits.index', ['status' => 'pending']));

            $response->assertOk();
            $response->assertSee($employee1->full_name);
            $response->assertDontSee($employee2->full_name);
        });

        it('can filter by exit type', function () {
            $employee1 = Employee::factory()->create(['company_id' => $this->company->id]);
            $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);

            EmployeeExit::factory()->resignation()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee1->id,
            ]);
            EmployeeExit::factory()->termination()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee2->id,
            ]);

            $response = $this->get(route('employee-exits.index', ['exit_type' => 'resignation']));

            $response->assertOk();
            $response->assertSee($employee1->full_name);
            $response->assertDontSee($employee2->full_name);
        });
    });

    describe('create', function () {
        it('displays create exit form', function () {
            $response = $this->get(route('employee-exits.create'));

            $response->assertOk();
            $response->assertViewIs('employee-exits.create');
        });

        it('can create exit for specific employee', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            $response = $this->get(route('employee-exits.create', ['employee_id' => $employee->id]));

            $response->assertOk();
            $response->assertViewHas('selectedEmployee');
        });
    });

    describe('store', function () {
        it('creates a new employee exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            $response = $this->post(route('employee-exits.store'), [
                'employee_id' => $employee->id,
                'exit_type' => 'resignation',
                'request_date' => '2026-02-20',
                'last_working_day' => '2026-03-20',
                'effective_date' => '2026-03-21',
                'reason' => 'Pindah ke perusahaan lain',
            ]);

            $response->assertRedirect(route('employee-exits.index'));
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('employee_exits', [
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'exit_type' => 'resignation',
                'status' => 'pending',
            ]);
        });

        it('validates required fields', function () {
            $response = $this->post(route('employee-exits.store'), []);

            $response->assertSessionHasErrors(['employee_id', 'exit_type', 'request_date', 'last_working_day', 'effective_date']);
        });

        it('validates dates order', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            $response = $this->post(route('employee-exits.store'), [
                'employee_id' => $employee->id,
                'exit_type' => 'resignation',
                'request_date' => '2026-03-20',
                'last_working_day' => '2026-02-20', // Before request_date
                'effective_date' => '2026-02-21',
            ]);

            $response->assertSessionHasErrors(['last_working_day']);
        });
    });

    describe('show', function () {
        it('displays exit details', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('employee-exits.show', $exit));

            $response->assertOk();
            $response->assertViewIs('employee-exits.show');
            $response->assertViewHas('employeeExit');
        });

        it('returns 404 for other company exit', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);
            $exit = EmployeeExit::factory()->create([
                'company_id' => $otherCompany->id,
                'employee_id' => $otherEmployee->id,
            ]);

            $response = $this->get(route('employee-exits.show', $exit));

            $response->assertNotFound();
        });
    });

    describe('edit', function () {
        it('displays edit form', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->pending()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('employee-exits.edit', $exit));

            $response->assertOk();
            $response->assertViewIs('employee-exits.edit');
        });

        it('cannot edit completed exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->completed()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('employee-exits.edit', $exit));

            $response->assertRedirect();
            $response->assertSessionHas('error');
        });
    });

    describe('update', function () {
        it('updates an exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->pending()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->put(route('employee-exits.update', $exit), [
                'employee_id' => $employee->id,
                'exit_type' => 'resignation',
                'request_date' => '2026-02-20',
                'last_working_day' => '2026-03-25',
                'effective_date' => '2026-03-26',
                'reason' => 'Updated reason',
            ]);

            $response->assertRedirect(route('employee-exits.show', $exit));
            $response->assertSessionHas('success');

            $this->assertDatabaseHas('employee_exits', [
                'id' => $exit->id,
                'reason' => 'Updated reason',
            ]);
        });
    });

    describe('approve', function () {
        it('approves a pending exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->pending()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->post(route('employee-exits.approve', $exit), [
                'approval_notes' => 'Approved as requested',
            ]);

            $response->assertRedirect(route('employee-exits.show', $exit));
            $response->assertSessionHas('success');

            $exit->refresh();
            expect($exit->status)->toBe('approved');
            expect($exit->approved_by)->toBe($this->user->id);
        });
    });

    describe('reject', function () {
        it('rejects a pending exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->pending()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->post(route('employee-exits.reject', $exit), [
                'approval_notes' => 'Cannot approve at this time',
            ]);

            $response->assertRedirect(route('employee-exits.show', $exit));
            $response->assertSessionHas('success');

            $exit->refresh();
            expect($exit->status)->toBe('rejected');
        });
    });

    describe('complete', function () {
        it('completes an approved exit with full checklist', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
                'user_id' => $user->id,
                'is_active' => true,
            ]);
            $exit = EmployeeExit::factory()->approved()->withChecklist()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->post(route('employee-exits.complete', $exit));

            $response->assertRedirect(route('employee-exits.show', $exit));
            $response->assertSessionHas('success');

            $exit->refresh();
            $employee->refresh();
            $user->refresh();

            expect($exit->status)->toBe('completed');
            expect($employee->is_active)->toBeFalse();
            expect($user->is_active)->toBeFalse();
        });

        it('cannot complete without full checklist', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->approved()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'assets_returned' => false,
            ]);

            $response = $this->post(route('employee-exits.complete', $exit));

            $response->assertRedirect();
            $response->assertSessionHas('error');

            $exit->refresh();
            expect($exit->status)->toBe('approved'); // Still approved, not completed
        });
    });

    describe('updateChecklist', function () {
        it('updates checklist items', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->approved()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->put(route('employee-exits.checklist', $exit), [
                'assets_returned' => true,
                'knowledge_transfer_done' => true,
                'final_settlement_done' => false,
                'exit_interview_done' => false,
            ]);

            $response->assertRedirect(route('employee-exits.show', $exit));

            $exit->refresh();
            expect($exit->assets_returned)->toBeTrue();
            expect($exit->knowledge_transfer_done)->toBeTrue();
            expect($exit->final_settlement_done)->toBeFalse();
        });
    });

    describe('destroy', function () {
        it('deletes a pending exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->pending()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->delete(route('employee-exits.destroy', $exit));

            $response->assertRedirect(route('employee-exits.index'));
            $response->assertSessionHas('success');

            $this->assertSoftDeleted('employee_exits', ['id' => $exit->id]);
        });

        it('cannot delete completed exit', function () {
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);
            $exit = EmployeeExit::factory()->completed()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->delete(route('employee-exits.destroy', $exit));

            $response->assertRedirect();
            $response->assertSessionHas('error');

            $this->assertNotSoftDeleted('employee_exits', ['id' => $exit->id]);
        });
    });
});
