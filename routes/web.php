<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BpjsKesSettingController;
use App\Http\Controllers\BpjsTkSettingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoSettingController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeExitController;
use App\Http\Controllers\EmployeePortal\AnnouncementController as PortalAnnouncementController;
use App\Http\Controllers\EmployeePortal\AttendanceController as PortalAttendanceController;
use App\Http\Controllers\EmployeePortal\DashboardController as PortalDashboardController;
use App\Http\Controllers\EmployeePortal\LeaveController as PortalLeaveController;
use App\Http\Controllers\EmployeePortal\OvertimeController as PortalOvertimeController;
use App\Http\Controllers\EmployeePortal\PayslipController as PortalPayslipController;
use App\Http\Controllers\EmployeePortal\ProfileController as PortalProfileController;
use App\Http\Controllers\EmployeePortal\ReimbursementController as PortalReimbursementController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\FaceRecognitionController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\Import\DepartmentImportController;
use App\Http\Controllers\Import\EmployeeImportController;
use App\Http\Controllers\Import\HolidayImportController;
use App\Http\Controllers\Import\LeaveTypeImportController;
use App\Http\Controllers\Import\PositionImportController;
use App\Http\Controllers\Import\WorkScheduleImportController;
use App\Http\Controllers\LeaveBalanceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OfficeLocationController;
use App\Http\Controllers\OrganizationChartController;
use App\Http\Controllers\OvertimeRequestController;
use App\Http\Controllers\OvertimeSettingController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollItemController;
use App\Http\Controllers\PayrollSettingController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\Pph21SettingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReimbursementCategoryController;
use App\Http\Controllers\ReimbursementController;
use App\Http\Controllers\Reports\AttendanceReportController;
use App\Http\Controllers\Reports\EmployeeReportController;
use App\Http\Controllers\Reports\LeaveReportController;
use App\Http\Controllers\Reports\PayrollReportController;
use App\Http\Controllers\SalaryComponentController;
use App\Http\Controllers\Settings\ApprovalWorkflowController;
use App\Http\Controllers\Settings\AttendanceSettingController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\CompanyProfileController;
use App\Http\Controllers\Settings\LarkIntegrationController;
use App\Http\Controllers\Settings\SessionController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Spt1721Controller;
use App\Http\Controllers\Superadmin\AuditLogController;
use App\Http\Controllers\Superadmin\BlockedIpController;
use App\Http\Controllers\Superadmin\EmailLogController;
use App\Http\Controllers\Superadmin\NotificationLogController;
use App\Http\Controllers\Superadmin\PaymentController;
use App\Http\Controllers\Superadmin\PaymentGatewayController;
use App\Http\Controllers\Superadmin\PlanController;
use App\Http\Controllers\Superadmin\QueueMonitorController;
use App\Http\Controllers\Superadmin\RateLimitLogController;
use App\Http\Controllers\Superadmin\SecurityLogController;
use App\Http\Controllers\Superadmin\SessionManagementController;
use App\Http\Controllers\Superadmin\SubscriptionController;
use App\Http\Controllers\Superadmin\SuperadminAuthController;
use App\Http\Controllers\Superadmin\SuperadminDashboardController;
use App\Http\Controllers\Superadmin\SystemHealthController;
use App\Http\Controllers\TaxForm1721A1Controller;
use App\Http\Controllers\ThrController;
use App\Http\Controllers\ThrSettingController;
use App\Http\Controllers\WorkScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // In single-tenant (on-premise) mode, skip SaaS landing page entirely.
    if (single_tenant_mode()) {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->is_superadmin ?? false) {
                return redirect()->route('superadmin.dashboard');
            }

            $adminRoles = ['admin', 'hr-manager', 'payroll-manager', 'manager'];
            if ($user->hasRole('employee') && ! $user->hasAnyRole($adminRoles)) {
                return redirect()->route('portal.dashboard');
            }

            return redirect()->route('dashboard');
        }

        return redirect()->route('login');
    }

    $plans = \App\Models\SubscriptionPlan::where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    return view('pages.landing', compact('plans'));
})->name('home');

Route::get('/pricing', function () {
    $plans = \App\Models\SubscriptionPlan::where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    return view('pages.pricing', compact('plans'));
})->middleware('abort_if_single_tenant')->name('pricing');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/privacy', function () {
    return view('pages.privacy');
})->name('privacy');

Route::get('/subscription-expired', function () {
    return view('pages.subscription-expired');
})->middleware('abort_if_single_tenant')->name('subscription.expired');

/*
|--------------------------------------------------------------------------
| Guest Routes (Auth)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Public registration is disabled in single-tenant (on-premise) mode.
    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->middleware('abort_if_single_tenant')
        ->name('register');

    // Register: 5 attempts per minute (spam protection)
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware(['throttle:5,1', 'abort_if_single_tenant']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Login: 5 attempts per minute (brute-force protection)
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');

    // Password Reset Routes
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');

    // Forgot password: 3 attempts per minute (email enumeration protection)
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');

    // Reset password: 5 attempts per minute
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Preview Route (TEMPORARY - Remove after Phase 1)
|--------------------------------------------------------------------------
*/
Route::get('/preview/dashboard', function () {
    return view('dashboard');
})->name('preview.dashboard');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

// Logout - accessible by all authenticated users
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

// Admin routes - redirect employees to portal
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Employee Management
    Route::resource('employees', EmployeeController::class);

    // Employee Password Reset (Admin)
    Route::get('employees/{employee}/reset-password', [EmployeeController::class, 'showResetPasswordForm'])
        ->name('employees.reset-password');
    Route::post('employees/{employee}/reset-password', [EmployeeController::class, 'resetPassword'])
        ->name('employees.reset-password.update');
    Route::post('employees/{employee}/generate-password', [EmployeeController::class, 'generatePassword'])
        ->name('employees.reset-password.generate');

    // Employee Documents
    Route::prefix('employees/{employee}/documents')->name('employees.documents.')->group(function () {
        Route::get('/', [EmployeeDocumentController::class, 'index'])->name('index');
        Route::get('/create', [EmployeeDocumentController::class, 'create'])->name('create');
        Route::post('/', [EmployeeDocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [EmployeeDocumentController::class, 'show'])->name('show');
        Route::get('/{document}/download', [EmployeeDocumentController::class, 'download'])->name('download');
        Route::delete('/{document}', [EmployeeDocumentController::class, 'destroy'])->name('destroy');
        Route::post('/{document}/verify', [EmployeeDocumentController::class, 'verify'])->name('verify');
        Route::post('/{document}/unverify', [EmployeeDocumentController::class, 'unverify'])->name('unverify');
    });

    // Employee Exit Management
    Route::post('employee-exits/{employee_exit}/approve', [EmployeeExitController::class, 'approve'])->name('employee-exits.approve');
    Route::post('employee-exits/{employee_exit}/reject', [EmployeeExitController::class, 'reject'])->name('employee-exits.reject');
    Route::post('employee-exits/{employee_exit}/complete', [EmployeeExitController::class, 'complete'])->name('employee-exits.complete');
    Route::put('employee-exits/{employee_exit}/checklist', [EmployeeExitController::class, 'updateChecklist'])->name('employee-exits.checklist');
    Route::resource('employee-exits', EmployeeExitController::class);

    // Organization Chart
    Route::get('organization-chart', [OrganizationChartController::class, 'index'])->name('organization-chart.index');
    Route::get('organization-chart/department-tree', [OrganizationChartController::class, 'departmentTree'])->name('organization-chart.department-tree');
    Route::get('organization-chart/employee-tree', [OrganizationChartController::class, 'employeeTree'])->name('organization-chart.employee-tree');

    // Department Management
    Route::resource('departments', DepartmentController::class)->except(['show']);

    // Position Management
    Route::resource('positions', PositionController::class)->except(['show']);

    // Work Schedule Management
    Route::resource('work-schedules', WorkScheduleController::class);

    // Holiday Management
    Route::get('holidays/calendar', [HolidayController::class, 'calendar'])->name('holidays.calendar');
    Route::get('holidays/events', [HolidayController::class, 'events'])->name('holidays.events');
    Route::post('holidays/generate', [HolidayController::class, 'generate'])->name('holidays.generate');
    Route::resource('holidays', HolidayController::class)->except(['show']);

    // Office Location Management
    Route::patch('office-locations/{officeLocation}/toggle-status', [OfficeLocationController::class, 'toggleStatus'])->name('office-locations.toggle-status');
    Route::post('office-locations/{officeLocation}/assign-employees', [OfficeLocationController::class, 'assignEmployees'])->name('office-locations.assign-employees');
    Route::delete('office-locations/{officeLocation}/remove-employee/{employee}', [OfficeLocationController::class, 'removeEmployee'])->name('office-locations.remove-employee');
    Route::resource('office-locations', OfficeLocationController::class);

    // Face Recognition Management
    Route::get('face-recognition', [FaceRecognitionController::class, 'index'])->name('face-recognition.index');
    Route::get('face-recognition/{employee}', [FaceRecognitionController::class, 'show'])->name('face-recognition.show');
    Route::post('face-recognition/{employee}', [FaceRecognitionController::class, 'store'])->name('face-recognition.store');
    Route::delete('face-recognition/{employee}', [FaceRecognitionController::class, 'destroy'])->name('face-recognition.destroy');

    // Attendance Management
    Route::get('attendances/report', [AttendanceController::class, 'report'])->name('attendances.report');
    Route::get('attendances/export', [AttendanceController::class, 'export'])->name('attendances.export');
    Route::resource('attendances', AttendanceController::class);
    Route::post('attendances/clock-in', [AttendanceController::class, 'clockIn'])->name('attendances.clock-in');
    Route::post('attendances/clock-out', [AttendanceController::class, 'clockOut'])->name('attendances.clock-out');

    // Leave Type Management
    Route::patch('leave-types/{leave_type}/toggle-status', [LeaveTypeController::class, 'toggleStatus'])->name('leave-types.toggle-status');
    Route::resource('leave-types', LeaveTypeController::class);

    // Leave Balance Management
    Route::post('leave-balances/generate-bulk', [LeaveBalanceController::class, 'generateBulk'])->name('leave-balances.generate-bulk');
    Route::resource('leave-balances', LeaveBalanceController::class)->except(['show']);

    // Leave Request Management
    Route::post('leave-requests/{leave_request}/approve', [LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
    Route::post('leave-requests/{leave_request}/reject', [LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
    Route::post('leave-requests/{leave_request}/cancel', [LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
    Route::resource('leave-requests', LeaveRequestController::class);

    // Salary Component Management
    Route::patch('salary-components/{salary_component}/toggle-status', [SalaryComponentController::class, 'toggleStatus'])->name('salary-components.toggle-status');
    Route::resource('salary-components', SalaryComponentController::class);

    // Employee Salary Management
    Route::resource('employee-salaries', EmployeeSalaryController::class);

    // Payroll Management
    Route::post('payrolls/{payroll}/process', [PayrollController::class, 'process'])->name('payrolls.process');
    Route::post('payrolls/{payroll}/approve', [PayrollController::class, 'approve'])->name('payrolls.approve');
    Route::post('payrolls/{payroll}/pay', [PayrollController::class, 'pay'])->name('payrolls.pay');
    Route::post('payrolls/{payroll}/cancel', [PayrollController::class, 'cancel'])->name('payrolls.cancel');
    Route::get('payrolls/{payroll}/export-bank', [PayrollController::class, 'exportBank'])->name('payrolls.export-bank');
    Route::resource('payrolls', PayrollController::class);

    // Payroll Item (Slip Gaji & Riwayat)
    Route::get('payroll-items', [PayrollItemController::class, 'index'])->name('payroll-items.index');
    Route::get('payroll-items/{payroll_item}', [PayrollItemController::class, 'show'])->name('payroll-items.show');
    Route::get('payroll-items/{payroll_item}/edit', [PayrollItemController::class, 'edit'])->name('payroll-items.edit');
    Route::put('payroll-items/{payroll_item}', [PayrollItemController::class, 'update'])->name('payroll-items.update');
    Route::post('payroll-items/{payroll_item}/details', [PayrollItemController::class, 'addDetail'])->name('payroll-items.add-detail');
    Route::delete('payroll-items/{payroll_item}/details/{detail}', [PayrollItemController::class, 'removeDetail'])->name('payroll-items.remove-detail');
    Route::post('payroll-items/{payroll_item}/recalculate', [PayrollItemController::class, 'recalculate'])->name('payroll-items.recalculate');
    Route::get('payroll-items/{payroll_item}/pdf', [PayrollItemController::class, 'pdf'])->name('payroll-items.pdf');

    // Demo Mode Settings
    Route::prefix('demo')->name('demo.')->group(function () {
        Route::get('/', [DemoSettingController::class, 'index'])->name('settings');
        Route::post('/switch-to-production', [DemoSettingController::class, 'switchToProduction'])->name('switch-to-production');
        Route::post('/reset', [DemoSettingController::class, 'resetDemoData'])->name('reset');
    });

    // PPh 21 Settings
    Route::prefix('pph21-settings')->name('pph21-settings.')->group(function () {
        Route::get('/', [Pph21SettingController::class, 'index'])->name('index');
        Route::put('/update-setting', [Pph21SettingController::class, 'updateSetting'])->name('update-setting');
        Route::post('/initialize-ptkp', [Pph21SettingController::class, 'initializePtkp'])->name('initialize-ptkp');
        Route::post('/initialize-rates', [Pph21SettingController::class, 'initializeRates'])->name('initialize-rates');
        Route::put('/ptkp/{ptkpSetting}', [Pph21SettingController::class, 'updatePtkp'])->name('update-ptkp');
        Route::put('/rates/{pph21Rate}', [Pph21SettingController::class, 'updateRate'])->name('update-rate');
        Route::post('/initialize-ter-rates', [Pph21SettingController::class, 'initializeTerRates'])->name('initialize-ter-rates');
        Route::put('/ter-rates/{pph21TerRate}', [Pph21SettingController::class, 'updateTerRate'])->name('update-ter-rate');
    });

    // BPJS Ketenagakerjaan Settings
    Route::prefix('bpjs-tk-settings')->name('bpjs-tk-settings.')->group(function () {
        Route::get('/', [BpjsTkSettingController::class, 'index'])->name('index');
        Route::put('/update', [BpjsTkSettingController::class, 'update'])->name('update');
        Route::post('/initialize-jkk-rates', [BpjsTkSettingController::class, 'initializeJkkRates'])->name('initialize-jkk-rates');
        Route::post('/calculate', [BpjsTkSettingController::class, 'calculate'])->name('calculate');
    });

    // BPJS Kesehatan Settings
    Route::prefix('bpjs-kes-settings')->name('bpjs-kes-settings.')->group(function () {
        Route::get('/', [BpjsKesSettingController::class, 'index'])->name('index');
        Route::put('/update', [BpjsKesSettingController::class, 'update'])->name('update');
        Route::post('/calculate', [BpjsKesSettingController::class, 'calculate'])->name('calculate');
    });

    // THR Settings
    Route::prefix('thr-settings')->name('thr-settings.')->group(function () {
        Route::get('/', [ThrSettingController::class, 'index'])->name('index');
        Route::put('/', [ThrSettingController::class, 'update'])->name('update');
    });

    // THR Management
    Route::prefix('thr')->name('thr.')->group(function () {
        Route::get('/', [ThrController::class, 'index'])->name('index');
        Route::get('/calculate', [ThrController::class, 'calculate'])->name('calculate');
        Route::post('/calculate', [ThrController::class, 'doCalculate'])->name('do-calculate');
        Route::post('/process', [ThrController::class, 'process'])->name('process');
        Route::post('/{thr}/pay', [ThrController::class, 'pay'])->name('pay');
        Route::post('/{thr}/cancel', [ThrController::class, 'cancel'])->name('cancel');
    });

    // Overtime Settings
    Route::prefix('overtime-settings')->name('overtime-settings.')->group(function () {
        Route::get('/', [OvertimeSettingController::class, 'index'])->name('index');
        Route::put('/', [OvertimeSettingController::class, 'update'])->name('update');
    });

    // Payroll Settings
    Route::prefix('payroll-settings')->name('payroll-settings.')->group(function () {
        Route::get('/', [PayrollSettingController::class, 'edit'])->name('edit');
        Route::put('/', [PayrollSettingController::class, 'update'])->name('update');
    });

    // Overtime Requests
    Route::prefix('overtime-requests')->name('overtime-requests.')->group(function () {
        Route::get('/', [OvertimeRequestController::class, 'index'])->name('index');
        Route::get('/create', [OvertimeRequestController::class, 'create'])->name('create');
        Route::post('/', [OvertimeRequestController::class, 'store'])->name('store');
        Route::get('/{overtimeRequest}', [OvertimeRequestController::class, 'show'])->name('show');
        Route::post('/{overtimeRequest}/approve', [OvertimeRequestController::class, 'approve'])->name('approve');
        Route::post('/{overtimeRequest}/reject', [OvertimeRequestController::class, 'reject'])->name('reject');
    });

    // Reimbursement Categories
    Route::resource('reimbursement-categories', ReimbursementCategoryController::class);

    // Reimbursements
    Route::prefix('reimbursements')->name('reimbursements.')->group(function () {
        Route::get('/', [ReimbursementController::class, 'index'])->name('index');
        Route::get('/create', [ReimbursementController::class, 'create'])->name('create');
        Route::post('/', [ReimbursementController::class, 'store'])->name('store');
        Route::get('/{reimbursement}', [ReimbursementController::class, 'show'])->name('show');
        Route::post('/{reimbursement}/approve', [ReimbursementController::class, 'approve'])->name('approve');
        Route::post('/{reimbursement}/reject', [ReimbursementController::class, 'reject'])->name('reject');
        Route::post('/{reimbursement}/pay', [ReimbursementController::class, 'pay'])->name('pay');
    });

    // Announcements
    Route::resource('announcements', AnnouncementController::class);
    Route::post('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::post('announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        // Company Profile
        Route::get('company-profile', [CompanyProfileController::class, 'index'])->name('company-profile.index');
        Route::put('company-profile', [CompanyProfileController::class, 'update'])->name('company-profile.update');
        Route::delete('company-profile/logo', [CompanyProfileController::class, 'deleteLogo'])->name('company-profile.delete-logo');
        Route::put('company-profile/settings', [CompanyProfileController::class, 'updateSettings'])->name('company-profile.update-settings');

        // User Management
        Route::resource('users', UserController::class);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Approval Workflows
        Route::resource('approval-workflows', ApprovalWorkflowController::class);
        Route::patch('approval-workflows/{approval_workflow}/toggle-status', [ApprovalWorkflowController::class, 'toggleStatus'])->name('approval-workflows.toggle-status');
        Route::post('approval-workflows/{approval_workflow}/steps', [ApprovalWorkflowController::class, 'storeStep'])->name('approval-workflows.steps.store');
        Route::put('approval-workflows/{approval_workflow}/steps/{step}', [ApprovalWorkflowController::class, 'updateStep'])->name('approval-workflows.steps.update');
        Route::delete('approval-workflows/{approval_workflow}/steps/{step}', [ApprovalWorkflowController::class, 'destroyStep'])->name('approval-workflows.steps.destroy');

        // Billing (disabled in single-tenant / on-premise mode)
        Route::middleware('abort_if_single_tenant')->group(function () {
            Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
            Route::get('billing/upgrade', [BillingController::class, 'upgrade'])->name('billing.upgrade');
            Route::post('billing/upgrade', [BillingController::class, 'processUpgrade'])->name('billing.process-upgrade');
            Route::post('billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
            Route::get('billing/invoices/{payment}', [BillingController::class, 'invoice'])->name('billing.invoice');
        });

        // Activity Logs
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{activity}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

        // Session Management
        Route::get('sessions', [SessionController::class, 'index'])->name('sessions.index');
        Route::delete('sessions/{id}', [SessionController::class, 'destroy'])->name('sessions.destroy');

        // Attendance Settings
        Route::get('attendance', [AttendanceSettingController::class, 'index'])->name('attendance.index');
        Route::put('attendance', [AttendanceSettingController::class, 'update'])->name('attendance.update');

        // Lark Integration Settings
        Route::get('lark', [LarkIntegrationController::class, 'index'])->name('lark.index');
        Route::put('lark', [LarkIntegrationController::class, 'update'])->name('lark.update');
        Route::post('lark/test-connection', [LarkIntegrationController::class, 'testConnection'])->name('lark.test-connection');
        Route::post('lark/sync-employees', [LarkIntegrationController::class, 'syncEmployees'])->name('lark.sync-employees');
        Route::post('lark/retry-sync', [LarkIntegrationController::class, 'retrySync'])->name('lark.retry-sync');
        Route::get('lark/mapping', [LarkIntegrationController::class, 'mapping'])->name('lark.mapping');
        Route::post('lark/mapping/{employee}', [LarkIntegrationController::class, 'saveEmployeeMapping'])->name('lark.mapping.save');
        Route::delete('lark/mapping/{employee}', [LarkIntegrationController::class, 'unmapEmployee'])->name('lark.mapping.destroy');

        // Role Management
        Route::resource('roles', \App\Http\Controllers\Settings\RoleController::class)
            ->except(['show'])
            ->middleware('permission:manage roles');

        // Permission Management
        Route::resource('permissions', \App\Http\Controllers\Settings\PermissionController::class)
            ->except(['show'])
            ->middleware('permission:manage roles');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        // Employee Reports
        Route::get('employees', [EmployeeReportController::class, 'index'])->name('employees');
        Route::get('employees/export', [EmployeeReportController::class, 'export'])->name('employees.export');
        Route::get('employees/by-department', [EmployeeReportController::class, 'byDepartment'])->name('employees.by-department');

        // Attendance Reports
        Route::get('attendance', [AttendanceReportController::class, 'index'])->name('attendance');
        Route::get('attendance/daily', [AttendanceReportController::class, 'daily'])->name('attendance.daily');
        Route::get('attendance/lateness', [AttendanceReportController::class, 'lateness'])->name('attendance.lateness');
        Route::get('attendance/export', [AttendanceReportController::class, 'export'])->name('attendance.export');

        // Leave Reports
        Route::get('leave', [LeaveReportController::class, 'index'])->name('leave');
        Route::get('leave/balance', [LeaveReportController::class, 'balance'])->name('leave.balance');
        Route::get('leave/by-type', [LeaveReportController::class, 'byType'])->name('leave.by-type');
        Route::get('leave/export', [LeaveReportController::class, 'export'])->name('leave.export');

        // Payroll Reports
        Route::get('payroll', [PayrollReportController::class, 'index'])->name('payroll');
        Route::get('payroll/by-department', [PayrollReportController::class, 'byDepartment'])->name('payroll.by-department');
        Route::get('payroll/tax-summary', [PayrollReportController::class, 'taxSummary'])->name('payroll.tax-summary');
        Route::get('payroll/export', [PayrollReportController::class, 'export'])->name('payroll.export');
    });

    // Tax Forms - Bukti Potong 1721-A1
    Route::prefix('tax-forms/1721a1')->name('tax-forms.1721a1.')->group(function () {
        Route::get('/', [TaxForm1721A1Controller::class, 'index'])->name('index');
        Route::get('/create', [TaxForm1721A1Controller::class, 'create'])->name('create');
        Route::post('/', [TaxForm1721A1Controller::class, 'store'])->name('store');
        Route::post('/generate-bulk', [TaxForm1721A1Controller::class, 'generateBulk'])->name('generate-bulk');
        Route::get('/pdf-bulk', [TaxForm1721A1Controller::class, 'pdfBulk'])->name('pdf-bulk');
        Route::get('/{taxForm1721a1}', [TaxForm1721A1Controller::class, 'show'])->name('show');
        Route::get('/{taxForm1721a1}/pdf', [TaxForm1721A1Controller::class, 'pdf'])->name('pdf');
        Route::post('/{taxForm1721a1}/regenerate', [TaxForm1721A1Controller::class, 'regenerate'])->name('regenerate');
        Route::delete('/{taxForm1721a1}', [TaxForm1721A1Controller::class, 'destroy'])->name('destroy');
    });

    // SPT Tahunan 1721
    Route::prefix('spt-1721')->name('spt-1721.')->group(function () {
        Route::get('/', [Spt1721Controller::class, 'index'])->name('index');
        Route::get('/create', [Spt1721Controller::class, 'create'])->name('create');
        Route::post('/', [Spt1721Controller::class, 'store'])->name('store');
        Route::get('/{spt1721}', [Spt1721Controller::class, 'show'])->name('show');
        Route::post('/{spt1721}/calculate', [Spt1721Controller::class, 'calculate'])->name('calculate');
        Route::post('/{spt1721}/sync-tax-forms', [Spt1721Controller::class, 'syncTaxForms'])->name('sync-tax-forms');
        Route::post('/{spt1721}/submit', [Spt1721Controller::class, 'submit'])->name('submit');
        Route::post('/{spt1721}/report', [Spt1721Controller::class, 'report'])->name('report');
        Route::post('/{spt1721}/create-pembetulan', [Spt1721Controller::class, 'createPembetulan'])->name('create-pembetulan');
        Route::put('/{spt1721}/update-signer', [Spt1721Controller::class, 'updateSigner'])->name('update-signer');
        Route::get('/{spt1721}/export/{type?}', [Spt1721Controller::class, 'exportExcel'])->name('export');
        Route::delete('/{spt1721}', [Spt1721Controller::class, 'destroy'])->name('destroy');
    });

    // Data Import
    Route::prefix('imports')->name('imports.')->group(function () {
        // Department Import
        Route::get('departments', [DepartmentImportController::class, 'index'])->name('departments.index');
        Route::get('departments/template', [DepartmentImportController::class, 'template'])->name('departments.template');
        Route::post('departments', [DepartmentImportController::class, 'store'])->name('departments.store');

        // Position Import
        Route::get('positions', [PositionImportController::class, 'index'])->name('positions.index');
        Route::get('positions/template', [PositionImportController::class, 'template'])->name('positions.template');
        Route::post('positions', [PositionImportController::class, 'store'])->name('positions.store');

        // Work Schedule Import
        Route::get('work-schedules', [WorkScheduleImportController::class, 'index'])->name('work-schedules.index');
        Route::get('work-schedules/template', [WorkScheduleImportController::class, 'template'])->name('work-schedules.template');
        Route::post('work-schedules', [WorkScheduleImportController::class, 'store'])->name('work-schedules.store');

        // Leave Type Import
        Route::get('leave-types', [LeaveTypeImportController::class, 'index'])->name('leave-types.index');
        Route::get('leave-types/template', [LeaveTypeImportController::class, 'template'])->name('leave-types.template');
        Route::post('leave-types', [LeaveTypeImportController::class, 'store'])->name('leave-types.store');

        // Employee Import
        Route::get('employees', [EmployeeImportController::class, 'index'])->name('employees.index');
        Route::get('employees/template', [EmployeeImportController::class, 'template'])->name('employees.template');
        Route::post('employees', [EmployeeImportController::class, 'store'])->name('employees.store');
        Route::get('employees/status/{importId}', [EmployeeImportController::class, 'status'])->name('employees.status');

        // Holiday Import
        Route::get('holidays', [HolidayImportController::class, 'index'])->name('holidays.index');
        Route::get('holidays/template', [HolidayImportController::class, 'template'])->name('holidays.template');
        Route::post('holidays', [HolidayImportController::class, 'store'])->name('holidays.store');
    });
});

/*
|--------------------------------------------------------------------------
| Employee Portal Routes
|--------------------------------------------------------------------------
*/

Route::prefix('portal')->name('portal.')->middleware(['auth', 'employee'])->group(function () {
    // Dashboard
    Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [PortalProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [PortalProfileController::class, 'update'])->name('profile.update');

    // Attendance
    Route::get('/attendance', [PortalAttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [PortalAttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [PortalAttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // Leave
    Route::get('/leave', [PortalLeaveController::class, 'index'])->name('leave.index');
    Route::get('/leave/create', [PortalLeaveController::class, 'create'])->name('leave.create');
    Route::post('/leave', [PortalLeaveController::class, 'store'])->name('leave.store');
    Route::post('/leave/{leaveRequest}/cancel', [PortalLeaveController::class, 'cancel'])->name('leave.cancel');

    // Payslips
    Route::get('/payslips', [PortalPayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{payrollItem}', [PortalPayslipController::class, 'show'])->name('payslips.show');
    Route::get('/payslips/{payrollItem}/pdf', [PortalPayslipController::class, 'pdf'])->name('payslips.pdf');

    // Overtime
    Route::get('/overtime', [PortalOvertimeController::class, 'index'])->name('overtime.index');
    Route::get('/overtime/create', [PortalOvertimeController::class, 'create'])->name('overtime.create');
    Route::post('/overtime', [PortalOvertimeController::class, 'store'])->name('overtime.store');
    Route::post('/overtime/{overtimeRequest}/cancel', [PortalOvertimeController::class, 'cancel'])->name('overtime.cancel');

    // Reimbursements
    Route::get('/reimbursements', [PortalReimbursementController::class, 'index'])->name('reimbursements.index');
    Route::get('/reimbursements/create', [PortalReimbursementController::class, 'create'])->name('reimbursements.create');
    Route::post('/reimbursements', [PortalReimbursementController::class, 'store'])->name('reimbursements.store');
    Route::get('/reimbursements/{reimbursement}', [PortalReimbursementController::class, 'show'])->name('reimbursements.show');

    // Announcements
    Route::get('/announcements', [PortalAnnouncementController::class, 'index'])->name('announcements.index');
    Route::get('/announcements/{announcement}', [PortalAnnouncementController::class, 'show'])->name('announcements.show');

    // Face Recognition Status
    Route::get('/face-recognition/status', [PortalAttendanceController::class, 'faceRecognitionStatus'])->name('face-recognition.status');
});

/*
|--------------------------------------------------------------------------
| Superadmin Routes
|--------------------------------------------------------------------------
*/

// Superadmin Auth (Guest)
Route::prefix('superadmin')->name('superadmin.')->middleware('abort_if_single_tenant')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [SuperadminAuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [SuperadminAuthController::class, 'login']);
    });

    Route::middleware(['auth', 'superadmin'])->group(function () {
        Route::post('logout', [SuperadminAuthController::class, 'logout'])->name('logout');

        // Dashboard
        Route::get('/', [SuperadminDashboardController::class, 'index'])->name('dashboard');

        // Company Management (View Only)
        Route::get('companies', [\App\Http\Controllers\Superadmin\CompanyController::class, 'index'])->name('companies.index');
        Route::get('companies/{company}', [\App\Http\Controllers\Superadmin\CompanyController::class, 'show'])->name('companies.show');

        // Plan Management
        Route::patch('plans/{plan}/toggle-status', [PlanController::class, 'toggleStatus'])->name('plans.toggle-status');
        Route::resource('plans', PlanController::class);

        // Subscription Management
        Route::patch('subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
        Route::patch('subscriptions/{subscription}/suspend', [SubscriptionController::class, 'suspend'])->name('subscriptions.suspend');
        Route::patch('subscriptions/{subscription}/extend', [SubscriptionController::class, 'extend'])->name('subscriptions.extend');
        Route::patch('subscriptions/{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
        Route::resource('subscriptions', SubscriptionController::class)->only(['index', 'show']);

        // Payment Gateway Management
        Route::patch('payment-gateways/{payment_gateway}/toggle-status', [PaymentGatewayController::class, 'toggleStatus'])->name('payment-gateways.toggle-status');
        Route::resource('payment-gateways', PaymentGatewayController::class);

        // Payment History
        Route::patch('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
        Route::patch('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
        Route::resource('payments', PaymentController::class)->only(['index', 'show']);

        // System Monitoring
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('health', [SystemHealthController::class, 'index'])->name('health');

            // Queue Monitor
            Route::post('queue/retry-all', [QueueMonitorController::class, 'retryAll'])->name('queue.retry-all');
            Route::post('queue/flush', [QueueMonitorController::class, 'flush'])->name('queue.flush');
            Route::post('queue/{id}/retry', [QueueMonitorController::class, 'retry'])->name('queue.retry');
            Route::delete('queue/{id}', [QueueMonitorController::class, 'destroy'])->name('queue.destroy');
            Route::get('queue/{id}', [QueueMonitorController::class, 'show'])->name('queue.show');
            Route::get('queue', [QueueMonitorController::class, 'index'])->name('queue.index');

            // Email Logs
            Route::post('email-logs/{emailLog}/resend', [EmailLogController::class, 'resend'])->name('email-logs.resend');
            Route::get('email-logs/{emailLog}', [EmailLogController::class, 'show'])->name('email-logs.show');
            Route::get('email-logs', [EmailLogController::class, 'index'])->name('email-logs.index');

            // Notifications
            Route::get('notifications', [NotificationLogController::class, 'index'])->name('notifications.index');

            // Audit Logs
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');

            // Session Management
            Route::get('sessions', [SessionManagementController::class, 'index'])->name('sessions.index');
            Route::delete('sessions/{id}', [SessionManagementController::class, 'destroy'])->name('sessions.destroy');

            // Rate Limit Logs
            Route::post('rate-limits/clear', [RateLimitLogController::class, 'clear'])->name('rate-limits.clear');
            Route::get('rate-limits', [RateLimitLogController::class, 'index'])->name('rate-limits.index');
        });

        // Security - Attack Logs
        Route::prefix('security')->name('security.')->group(function () {
            Route::post('logs/clear', [SecurityLogController::class, 'clear'])->name('logs.clear');
            Route::post('logs/{securityLog}/block-ip', [SecurityLogController::class, 'blockIp'])->name('logs.block-ip');
            Route::resource('logs', SecurityLogController::class)->only(['index', 'show', 'destroy'])->parameters(['logs' => 'securityLog']);

            // Blocked IPs
            Route::post('blocked-ips/{blockedIp}/unblock', [BlockedIpController::class, 'unblock'])->name('blocked-ips.unblock');
            Route::resource('blocked-ips', BlockedIpController::class)->only(['index', 'create', 'store', 'destroy']);
        });
    });
});
