<?php

use App\Http\Controllers\Api\V1\Admin as AdminApi;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\EmployeeOfficeController;
use App\Http\Controllers\Api\V1\FaceRecognitionController;
use App\Http\Controllers\Api\V1\LeaveController;
use App\Http\Controllers\Api\V1\OfficeLocationController;
use App\Http\Controllers\Api\V1\OvertimeController;
use App\Http\Controllers\Api\V1\PayslipController;
use App\Http\Controllers\Api\V1\ReimbursementController;
use App\Http\Controllers\Api\V1\ScheduleController;
use App\Http\Controllers\Api\V1\TaxFormController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Payment Gateway Webhooks
|--------------------------------------------------------------------------
*/

Route::prefix('webhooks')
    ->middleware(['throttle:60,1', 'abort_if_single_tenant']) // 60 requests per minute for webhooks
    ->group(function () {
        Route::post('/xendit', [WebhookController::class, 'xendit'])->name('webhooks.xendit');
        Route::post('/midtrans', [WebhookController::class, 'midtrans'])->name('webhooks.midtrans');
    });

/*
|--------------------------------------------------------------------------
| Mobile App API v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Authentication (public) - with strict rate limiting
    Route::prefix('auth')->name('auth.')->group(function () {
        // Login: 5 attempts per minute per IP (brute-force protection)
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login');

        // Demo register: 3 attempts per minute per IP (spam protection)
        Route::post('/demo-register', [AuthController::class, 'demoRegister'])
            ->middleware('throttle:3,1')
            ->name('demo-register');

        // Forgot password: 5 attempts per minute per IP
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:5,1')
            ->name('forgot-password');
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
            Route::patch('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
            Route::post('/change-password', [AuthController::class, 'changePassword'])->name('change-password');
            Route::delete('/delete-account', [AuthController::class, 'deleteAccount'])->name('delete-account');
        });

        // Dashboard
        Route::prefix('dashboard')->name('dashboard.')->group(function () {
            Route::get('/', [DashboardController::class, 'index'])->name('index');
            Route::get('/attendance-chart', [DashboardController::class, 'attendanceChart'])->name('attendance-chart');
            Route::get('/quick-stats', [DashboardController::class, 'quickStats'])->name('quick-stats');
        });

        // Approvals (Manager)
        Route::prefix('approvals')->name('approvals.')->group(function () {
            Route::get('/pending', [ApprovalController::class, 'pending'])->name('pending');
            Route::get('/history', [ApprovalController::class, 'history'])->name('history');
            Route::post('/leave/{id}/approve', [ApprovalController::class, 'approveLeave'])->name('leave.approve');
            Route::post('/leave/{id}/reject', [ApprovalController::class, 'rejectLeave'])->name('leave.reject');
            Route::post('/overtime/{id}/approve', [ApprovalController::class, 'approveOvertime'])->name('overtime.approve');
            Route::post('/overtime/{id}/reject', [ApprovalController::class, 'rejectOvertime'])->name('overtime.reject');
            Route::post('/reimbursement/{id}/approve', [ApprovalController::class, 'approveReimbursement'])->name('reimbursement.approve');
            Route::post('/reimbursement/{id}/reject', [ApprovalController::class, 'rejectReimbursement'])->name('reimbursement.reject');
        });

        // Schedule
        Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

        // Attendance
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/today', [AttendanceController::class, 'today'])->name('today');
            Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
            Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');
            Route::get('/history', [AttendanceController::class, 'history'])->name('history');
            Route::get('/summary', [AttendanceController::class, 'summary'])->name('summary');
        });

        // Leave Requests
        Route::prefix('leaves')->name('leaves.')->group(function () {
            Route::get('/', [LeaveController::class, 'index'])->name('index');
            Route::post('/', [LeaveController::class, 'store'])->name('store');
            Route::get('/balance', [LeaveController::class, 'balance'])->name('balance');
            Route::get('/types', [LeaveController::class, 'types'])->name('types');
            Route::get('/{leave}', [LeaveController::class, 'show'])->name('show');
            Route::post('/{leave}/cancel', [LeaveController::class, 'cancel'])->name('cancel');
        });

        // Overtime Requests
        Route::prefix('overtimes')->name('overtimes.')->group(function () {
            Route::get('/', [OvertimeController::class, 'index'])->name('index');
            Route::post('/', [OvertimeController::class, 'store'])->name('store');
            Route::get('/summary', [OvertimeController::class, 'summary'])->name('summary');
            Route::get('/{overtime}', [OvertimeController::class, 'show'])->name('show');
            Route::post('/{overtime}/cancel', [OvertimeController::class, 'cancel'])->name('cancel');
        });

        // Payslips
        Route::prefix('payslips')->name('payslips.')->group(function () {
            Route::get('/', [PayslipController::class, 'index'])->name('index');
            Route::get('/summary', [PayslipController::class, 'summary'])->name('summary');
            Route::get('/{payslip}', [PayslipController::class, 'show'])->name('show');
            Route::get('/{payslip}/download', [PayslipController::class, 'download'])->name('download');
            Route::get('/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('pdf');
        });

        // Tax Forms (Bukti Potong 1721-A1)
        Route::prefix('tax-forms')->name('tax-forms.')->group(function () {
            Route::get('/', [TaxFormController::class, 'index'])->name('index');
            Route::get('/years', [TaxFormController::class, 'years'])->name('years');
            Route::get('/{id}', [TaxFormController::class, 'show'])->name('show');
            Route::get('/{id}/download', [TaxFormController::class, 'download'])->name('download');
            Route::get('/{id}/pdf', [TaxFormController::class, 'pdf'])->name('pdf');
        });

        // Announcements
        Route::prefix('announcements')->name('announcements.')->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('index');
            Route::get('/unread-count', [AnnouncementController::class, 'unreadCount'])->name('unread-count');
            Route::get('/{announcement}', [AnnouncementController::class, 'show'])->name('show');
            Route::post('/{announcement}/read', [AnnouncementController::class, 'markAsRead'])->name('read');
        });

        // Reimbursements
        Route::prefix('reimbursements')->name('reimbursements.')->group(function () {
            Route::get('/', [ReimbursementController::class, 'index'])->name('index');
            Route::post('/', [ReimbursementController::class, 'store'])->name('store');
            Route::get('/categories', [ReimbursementController::class, 'categories'])->name('categories');
            Route::get('/summary', [ReimbursementController::class, 'summary'])->name('summary');
            Route::get('/{reimbursement}', [ReimbursementController::class, 'show'])->name('show');
        });

        // Office Locations
        Route::prefix('office-locations')->name('office-locations.')->group(function () {
            Route::get('/', [OfficeLocationController::class, 'index'])->name('index');
            Route::get('/assigned', [OfficeLocationController::class, 'assigned'])->name('assigned');
            Route::post('/validate-gps', [OfficeLocationController::class, 'validateGps'])->name('validate-gps');
            Route::get('/{id}', [OfficeLocationController::class, 'show'])->name('show');
        });

        // Face Recognition
        Route::prefix('face-recognition')->name('face-recognition.')->group(function () {
            Route::get('/status', [FaceRecognitionController::class, 'status'])->name('status');
            Route::post('/enroll', [FaceRecognitionController::class, 'enroll'])->name('enroll');
            Route::post('/verify', [FaceRecognitionController::class, 'verify'])->name('verify');
            Route::delete('/enrollment', [FaceRecognitionController::class, 'removeEnrollment'])->name('enrollment.destroy');
        });

        // Device Tokens (Push Notifications)
        Route::prefix('device-tokens')->name('device-tokens.')->group(function () {
            Route::get('/', [DeviceTokenController::class, 'index'])->name('index');
            Route::post('/register', [DeviceTokenController::class, 'register'])->name('register');
            Route::delete('/unregister', [DeviceTokenController::class, 'unregister'])->name('unregister');
            Route::post('/refresh', [DeviceTokenController::class, 'refresh'])->name('refresh');
        });

        // Employee Office Assignments (HRD)
        Route::prefix('employees/{employee}/offices')->name('employees.offices.')->group(function () {
            Route::get('/', [EmployeeOfficeController::class, 'index'])->name('index');
            Route::post('/', [EmployeeOfficeController::class, 'store'])->name('store');
            Route::delete('/', [EmployeeOfficeController::class, 'destroyAll'])->name('destroy-all');
            Route::delete('/{office}', [EmployeeOfficeController::class, 'destroy'])->name('destroy');
            Route::patch('/{office}/set-primary', [EmployeeOfficeController::class, 'setPrimary'])->name('set-primary');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard API (Next.js & External Clients)
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        // Admin Auth (Public)
        Route::post('/auth/login', [AdminApi\AuthController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('auth.login');

        // Admin Protected Routes
        Route::middleware('auth:sanctum')->group(function () {
            // Auth Profile & Session
            Route::get('/auth/me', [AdminApi\AuthController::class, 'me'])->name('auth.me');
            Route::post('/auth/logout', [AdminApi\AuthController::class, 'logout'])->name('auth.logout');

            // Dashboard Stats
            Route::get('/dashboard/stats', [AdminApi\DashboardController::class, 'stats'])->name('dashboard.stats');

            // Superadmin Multi-Tenant Management
            Route::prefix('superadmin')->name('superadmin.')->group(function () {
                Route::get('/companies/options', [AdminApi\Superadmin\CompanyController::class, 'options'])->name('companies.options');
                Route::match(['post', 'patch'], '/companies/{id}/toggle-status', [AdminApi\Superadmin\CompanyController::class, 'toggleStatus'])->name('companies.toggle-status');
                Route::apiResource('companies', AdminApi\Superadmin\CompanyController::class);
            });

            // Master Organisasi
            Route::apiResource('departments', AdminApi\DepartmentController::class);
            Route::apiResource('positions', AdminApi\PositionController::class);

            // Lokasi Kantor
            Route::match(['post', 'patch'], '/office-locations/{id}/toggle-status', [AdminApi\OfficeLocationController::class, 'toggleStatus'])->name('office-locations.toggle-status');
            Route::post('/office-locations/{id}/assign-employees', [AdminApi\OfficeLocationController::class, 'assignEmployees'])->name('office-locations.assign-employees');
            Route::delete('/office-locations/{id}/remove-employee/{employeeId}', [AdminApi\OfficeLocationController::class, 'removeEmployee'])->name('office-locations.remove-employee');
            Route::apiResource('office-locations', AdminApi\OfficeLocationController::class);

            // Karyawan
            Route::get('/employees/options', [AdminApi\EmployeeController::class, 'options'])->name('employees.options');
            Route::post('/employees/{id}/reset-password', [AdminApi\EmployeeController::class, 'resetPassword'])->name('employees.reset-password');
            Route::apiResource('employees', AdminApi\EmployeeController::class);

            // Jadwal Kerja & Hari Libur
            Route::apiResource('work-schedules', AdminApi\WorkScheduleController::class);
            Route::post('/holidays/generate', [AdminApi\HolidayController::class, 'generate'])->name('holidays.generate');
            Route::apiResource('holidays', AdminApi\HolidayController::class);

            // Kehadiran (Presensi)
            Route::get('/attendances/report', [AdminApi\AttendanceController::class, 'report'])->name('attendances.report');
            Route::apiResource('attendances', AdminApi\AttendanceController::class);

            // Cuti
            Route::match(['post', 'patch'], '/leave-types/{id}/toggle-status', [AdminApi\LeaveTypeController::class, 'toggleStatus'])->name('leave-types.toggle-status');
            Route::apiResource('leave-types', AdminApi\LeaveTypeController::class);
            Route::post('/leave-balances/generate-bulk', [AdminApi\LeaveBalanceController::class, 'generateBulk'])->name('leave-balances.generate-bulk');
            Route::apiResource('leave-balances', AdminApi\LeaveBalanceController::class);
            Route::post('/leave-requests/{id}/approve', [AdminApi\LeaveRequestController::class, 'approve'])->name('leave-requests.approve');
            Route::post('/leave-requests/{id}/reject', [AdminApi\LeaveRequestController::class, 'reject'])->name('leave-requests.reject');
            Route::post('/leave-requests/{id}/cancel', [AdminApi\LeaveRequestController::class, 'cancel'])->name('leave-requests.cancel');
            Route::apiResource('leave-requests', AdminApi\LeaveRequestController::class)->only(['index', 'store', 'show']);

            // Lembur
            Route::get('/overtime-settings', [AdminApi\OvertimeSettingController::class, 'show'])->name('overtime-settings.show');
            Route::match(['put', 'patch'], '/overtime-settings', [AdminApi\OvertimeSettingController::class, 'update'])->name('overtime-settings.update');
            Route::post('/overtime-requests/{id}/approve', [AdminApi\OvertimeRequestController::class, 'approve'])->name('overtime-requests.approve');
            Route::post('/overtime-requests/{id}/reject', [AdminApi\OvertimeRequestController::class, 'reject'])->name('overtime-requests.reject');
            Route::apiResource('overtime-requests', AdminApi\OvertimeRequestController::class)->only(['index', 'store', 'show']);

            // Reimbursement
            Route::apiResource('reimbursement-categories', AdminApi\ReimbursementCategoryController::class);
            Route::post('/reimbursements/{id}/approve', [AdminApi\ReimbursementController::class, 'approve'])->name('reimbursements.approve');
            Route::post('/reimbursements/{id}/reject', [AdminApi\ReimbursementController::class, 'reject'])->name('reimbursements.reject');
            Route::post('/reimbursements/{id}/pay', [AdminApi\ReimbursementController::class, 'pay'])->name('reimbursements.pay');
            Route::apiResource('reimbursements', AdminApi\ReimbursementController::class)->only(['index', 'store', 'show']);

            // Komponen & Pengaturan Gaji
            Route::match(['post', 'patch'], '/salary-components/{id}/toggle-status', [AdminApi\SalaryComponentController::class, 'toggleStatus'])->name('salary-components.toggle-status');
            Route::apiResource('salary-components', AdminApi\SalaryComponentController::class);
            Route::apiResource('employee-salaries', AdminApi\EmployeeSalaryController::class)->only(['index', 'store', 'show', 'update']);

            // Payroll Siklus & Slip
            Route::get('/payroll-settings', [AdminApi\PayrollSettingController::class, 'show'])->name('payroll-settings.show');
            Route::match(['put', 'patch'], '/payroll-settings', [AdminApi\PayrollSettingController::class, 'update'])->name('payroll-settings.update');
            Route::post('/payrolls/{id}/process', [AdminApi\PayrollController::class, 'process'])->name('payrolls.process');
            Route::post('/payrolls/{id}/approve', [AdminApi\PayrollController::class, 'approve'])->name('payrolls.approve');
            Route::post('/payrolls/{id}/pay', [AdminApi\PayrollController::class, 'pay'])->name('payrolls.pay');
            Route::post('/payrolls/{id}/cancel', [AdminApi\PayrollController::class, 'cancel'])->name('payrolls.cancel');
            Route::get('/payrolls/{id}/export-bank', [AdminApi\PayrollController::class, 'exportBank'])->name('payrolls.export-bank');
            Route::apiResource('payrolls', AdminApi\PayrollController::class);

            Route::post('/payroll-items/{id}/recalculate', [AdminApi\PayrollItemController::class, 'recalculate'])->name('payroll-items.recalculate');
            Route::apiResource('payroll-items', AdminApi\PayrollItemController::class)->only(['index', 'show', 'update']);

            // Pengumuman
            Route::post('/announcements/{id}/publish', [AdminApi\AnnouncementController::class, 'publish'])->name('announcements.publish');
            Route::post('/announcements/{id}/unpublish', [AdminApi\AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');
            Route::apiResource('announcements', AdminApi\AnnouncementController::class);

            // Pengaturan Sistem & User Admin
            Route::prefix('settings')->name('settings.')->group(function () {
                // Profil Perusahaan
                Route::get('/company-profile', [AdminApi\Settings\CompanyProfileController::class, 'show'])->name('company-profile.show');
                Route::match(['put', 'patch'], '/company-profile', [AdminApi\Settings\CompanyProfileController::class, 'update'])->name('company-profile.update');
                Route::post('/company-profile/logo', [AdminApi\Settings\CompanyProfileController::class, 'uploadLogo'])->name('company-profile.upload-logo');
                Route::delete('/company-profile/logo', [AdminApi\Settings\CompanyProfileController::class, 'deleteLogo'])->name('company-profile.delete-logo');

                // Pengaturan Presensi
                Route::get('/attendance', [AdminApi\Settings\AttendanceSettingController::class, 'show'])->name('attendance.show');
                Route::match(['put', 'patch'], '/attendance', [AdminApi\Settings\AttendanceSettingController::class, 'update'])->name('attendance.update');

                // User Admin & Roles
                Route::get('/users/roles', [AdminApi\Settings\UserController::class, 'roles'])->name('users.roles');
                Route::match(['post', 'patch'], '/users/{id}/toggle-status', [AdminApi\Settings\UserController::class, 'toggleStatus'])->name('users.toggle-status');
                Route::apiResource('users', AdminApi\Settings\UserController::class);
            });
        });
    });
});
