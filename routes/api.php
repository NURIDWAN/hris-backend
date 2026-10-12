<?php

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
});
