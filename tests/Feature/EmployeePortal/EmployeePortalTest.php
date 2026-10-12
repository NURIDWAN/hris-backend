<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();

    // Set team context for role creation
    setPermissionsTeamId($this->company->id);

    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Role::create(['name' => 'employee', 'guard_name' => 'web']);

    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->position = Position::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
    ]);

    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Create employee user
    $this->employeeUser = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
    ]);
    $this->employeeUser->assignRole('employee');

    // Create employee record linked to user
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
        'work_schedule_id' => $this->workSchedule->id,
        'user_id' => $this->employeeUser->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'base_salary' => 10000000,
    ]);
});

describe('Employee Portal Access', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get('/portal');

        $response->assertRedirect('/login');
    });

    it('denies access to admin users', function () {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        $response = $this->get('/portal');

        $response->assertStatus(403);
    });

    it('allows employee role to access portal', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal');

        $response->assertStatus(200);
        $response->assertViewIs('portal.dashboard');
    });
});

describe('Employee Portal Dashboard', function () {
    it('displays employee information', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal');

        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertSee($this->employee->employee_id);
    });

    it('displays current attendance status', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal');

        $response->assertStatus(200);
        $response->assertSee('Absensi Hari Ini');
    });

    it('displays leave balance summary', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'entitled_days' => 12,
            'used_days' => 2,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal');

        $response->assertStatus(200);
        $response->assertSee('Sisa Cuti');
    });

    it('displays recent payslips', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
        ]);

        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employee->id,
            'gross_salary' => 10000000,
            'net_salary' => 8500000,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal');

        $response->assertStatus(200);
        $response->assertSee('Slip Gaji');
    });
});

describe('Employee Portal Profile', function () {
    it('displays employee profile', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/profile');

        $response->assertStatus(200);
        $response->assertViewIs('portal.profile');
        $response->assertSee('John');
        $response->assertSee('Doe');
        $response->assertSee($this->employee->email);
    });

    it('allows employee to update personal info', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->put('/portal/profile', [
            'phone' => '08123456789',
            'address' => 'Jl. Test No. 123',
            'city' => 'Jakarta',
            'postal_code' => '12345',
            'emergency_contact_name' => 'Jane Doe',
            'emergency_contact_phone' => '08987654321',
            'emergency_contact_relationship' => 'Spouse',
        ]);

        $response->assertRedirect('/portal/profile');
        $response->assertSessionHas('success');

        $this->employee->refresh();
        expect($this->employee->phone)->toBe('08123456789');
        expect($this->employee->address)->toBe('Jl. Test No. 123');
    });

    it('validates profile update request', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->put('/portal/profile', [
            'phone' => '', // Required
        ]);

        $response->assertSessionHasErrors(['phone']);
    });
});

describe('Employee Portal Attendance', function () {
    it('displays attendance page', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/attendance');

        $response->assertStatus(200);
        $response->assertViewIs('portal.attendance');
    });

    it('allows employee to clock in', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->post('/portal/attendance/clock-in');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
        ]);
    });

    it('prevents double clock in', function () {
        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'clock_in' => now()->setTime(8, 0),
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post('/portal/attendance/clock-in');

        $response->assertSessionHas('error');
    });

    it('allows employee to clock out', function () {
        Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'clock_in' => now()->setTime(8, 0),
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post('/portal/attendance/clock-out');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });

    it('displays attendance history', function () {
        Attendance::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/attendance');

        $response->assertStatus(200);
    });
});

describe('Employee Portal Leave', function () {
    beforeEach(function () {
        $this->leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
            'is_active' => true,
        ]);

        $this->leaveBalance = LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'entitled_days' => 12,
            'used_days' => 0,
        ]);
    });

    it('displays leave request page', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/leave');

        $response->assertStatus(200);
        $response->assertViewIs('portal.leave.index');
    });

    it('displays leave request form', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/leave/create');

        $response->assertStatus(200);
        $response->assertViewIs('portal.leave.create');
        $response->assertSee('Cuti Tahunan');
    });

    it('allows employee to submit leave request', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->post('/portal/leave', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(9)->toDateString(),
            'reason' => 'Family vacation',
        ]);

        $response->assertRedirect('/portal/leave');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
        ]);
    });

    it('validates leave request', function () {
        $this->actingAs($this->employeeUser);

        $response = $this->post('/portal/leave', [
            'leave_type_id' => '',
            'start_date' => '',
            'end_date' => '',
            'reason' => '',
        ]);

        $response->assertSessionHasErrors(['leave_type_id', 'start_date', 'end_date', 'reason']);
    });

    it('prevents leave request when insufficient balance', function () {
        $this->leaveBalance->update(['entitled_days' => 2, 'used_days' => 2]);

        $this->actingAs($this->employeeUser);

        $response = $this->post('/portal/leave', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(12)->toDateString(), // 6 days
            'reason' => 'Long vacation',
        ]);

        $response->assertSessionHasErrors();
    });

    it('displays leave request history', function () {
        LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/leave');

        $response->assertStatus(200);
    });

    it('allows employee to cancel pending leave request', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->post("/portal/leave/{$leaveRequest->id}/cancel");

        $response->assertRedirect('/portal/leave');
        $response->assertSessionHas('success');

        $leaveRequest->refresh();
        expect($leaveRequest->status)->toBe('cancelled');
    });
});

describe('Employee Portal Payslips', function () {
    it('displays payslip list', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
        ]);

        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employee->id,
            'gross_salary' => 10000000,
            'net_salary' => 8500000,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get('/portal/payslips');

        $response->assertStatus(200);
        $response->assertViewIs('portal.payslips.index');
    });

    it('displays payslip detail', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
        ]);

        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employee->id,
            'gross_salary' => 10000000,
            'total_deductions' => 1500000,
            'net_salary' => 8500000,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get("/portal/payslips/{$payrollItem->id}");

        $response->assertStatus(200);
        $response->assertViewIs('portal.payslips.show');
    });

    it('prevents viewing other employee payslips', function () {
        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
        ]);

        $otherPayrollItem = PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get("/portal/payslips/{$otherPayrollItem->id}");

        $response->assertStatus(403);
    });

    it('allows employee to download payslip PDF', function () {
        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'paid',
        ]);

        $payrollItem = PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employee->id,
        ]);

        $this->actingAs($this->employeeUser);

        $response = $this->get("/portal/payslips/{$payrollItem->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    });
});
