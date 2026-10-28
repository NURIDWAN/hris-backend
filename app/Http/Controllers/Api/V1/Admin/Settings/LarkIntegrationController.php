<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\Employee;
use App\Models\LarkIntegration;
use App\Services\Lark\LarkAttendanceService;
use App\Services\Lark\LarkAuthService;
use App\Services\Lark\LarkEmployeeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LarkIntegrationController extends AdminBaseController
{
    public function show(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $integration = $company->larkIntegration()->first();

        $totalEmployees = $company->employees()->where('is_active', true)->count();
        $mappedEmployees = $company->employees()->where('is_active', true)->whereNotNull('lark_user_id')->count();

        $today = $company->today();
        $todayAttendances = $company->attendances()->whereDate('date', $today)->count();
        $syncedAttendances = $company->attendances()->whereDate('date', $today)->whereNotNull('lark_synced_at')->count();
        $failedAttendances = $company->attendances()->whereDate('date', $today)->whereNull('lark_synced_at')->whereNotNull('lark_sync_error')->count();

        $stats = [
            'total_employees' => $totalEmployees,
            'mapped_employees' => $mappedEmployees,
            'unmapped_employees' => max(0, $totalEmployees - $mappedEmployees),
            'today_attendances' => $todayAttendances,
            'synced_attendances' => $syncedAttendances,
            'failed_attendances' => $failedAttendances,
        ];

        $webhookUrl = url('/api/v1/webhooks/lark');

        $integrationData = [
            'id' => $integration?->id,
            'company_id' => $company->id,
            'app_id' => $integration?->app_id ?? '',
            'has_app_secret' => ! empty($integration?->app_secret),
            'is_active' => (bool) ($integration?->is_active ?? false),
            'push_clock_in' => (bool) ($integration?->push_clock_in ?? true),
            'push_clock_out' => (bool) ($integration?->push_clock_out ?? true),
            'enable_bot_notifications' => (bool) ($integration?->enable_bot_notifications ?? false),
            'bot_webhook_url' => $integration?->bot_webhook_url,
            'verification_token' => $integration?->verification_token,
            'has_encrypt_key' => ! empty($integration?->encrypt_key),
            'webhook_url' => $webhookUrl,
            'last_synced_at' => $integration?->last_synced_at?->toISOString(),
            'settings' => $integration?->settings,
            'created_at' => $integration?->created_at?->toISOString(),
            'updated_at' => $integration?->updated_at?->toISOString(),
        ];

        return $this->successResponse([
            'integration' => $integrationData,
            'stats' => $stats,
        ], 'Pengaturan integrasi Lark berhasil dimuat');
    }

    public function update(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $integration = $company->larkIntegration;

        $rules = [
            'app_id' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'push_clock_in' => ['required', 'boolean'],
            'push_clock_out' => ['required', 'boolean'],
            'enable_bot_notifications' => ['nullable', 'boolean'],
            'bot_webhook_url' => ['nullable', 'url', 'max:500'],
            'verification_token' => ['nullable', 'string', 'max:100'],
            'encrypt_key' => ['nullable', 'string', 'max:255'],
        ];

        if (! $integration) {
            $rules['app_secret'] = ['required', 'string'];
        } else {
            $rules['app_secret'] = ['nullable', 'string'];
        }

        $validated = $request->validate($rules);

        $data = [
            'app_id' => trim($validated['app_id']),
            'is_active' => (bool) $validated['is_active'],
            'push_clock_in' => (bool) $validated['push_clock_in'],
            'push_clock_out' => (bool) $validated['push_clock_out'],
            'enable_bot_notifications' => (bool) ($validated['enable_bot_notifications'] ?? false),
            'bot_webhook_url' => $validated['bot_webhook_url'] ?? null,
            'verification_token' => ! empty($validated['verification_token']) ? trim($validated['verification_token']) : ($integration?->verification_token ?? null),
        ];

        if (! empty($validated['app_secret'])) {
            $data['app_secret'] = trim($validated['app_secret']);
        }

        if (array_key_exists('encrypt_key', $validated)) {
            if (! empty($validated['encrypt_key'])) {
                $data['encrypt_key'] = trim($validated['encrypt_key']);
            }
        }

        if ($integration) {
            $integration->update($data);
            $integration->refresh();
        } else {
            $integration = $company->larkIntegration()->create($data);
        }

        $formatted = [
            'id' => $integration->id,
            'company_id' => $integration->company_id,
            'app_id' => $integration->app_id,
            'has_app_secret' => ! empty($integration->app_secret),
            'is_active' => (bool) $integration->is_active,
            'push_clock_in' => (bool) $integration->push_clock_in,
            'push_clock_out' => (bool) $integration->push_clock_out,
            'enable_bot_notifications' => (bool) $integration->enable_bot_notifications,
            'bot_webhook_url' => $integration->bot_webhook_url,
            'verification_token' => $integration->verification_token,
            'has_encrypt_key' => ! empty($integration->encrypt_key),
            'webhook_url' => url('/api/v1/webhooks/lark'),
            'last_synced_at' => $integration->last_synced_at?->toISOString(),
            'settings' => $integration->settings,
            'created_at' => $integration->created_at?->toISOString(),
            'updated_at' => $integration->updated_at?->toISOString(),
        ];

        return $this->successResponse($formatted, 'Pengaturan integrasi Lark berhasil disimpan');
    }

    public function testConnection(Request $request, LarkAuthService $authService): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $integration = $company->larkIntegration;

        $appId = $request->input('app_id') ?: $integration?->app_id;
        $appSecret = $request->input('app_secret') ?: $integration?->app_secret;

        if (empty($appId) || empty($appSecret)) {
            return $this->errorResponse('App ID dan App Secret wajib diisi untuk menguji koneksi.', 422);
        }

        $result = $authService->testCredentials($appId, $appSecret);

        if (! ($result['success'] ?? false)) {
            return $this->errorResponse($result['message'] ?? 'Gagal terhubung ke Lark.', 400, $result);
        }

        return $this->successResponse($result, $result['message']);
    }

    public function syncEmployees(Request $request, LarkEmployeeService $employeeService): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        try {
            $result = $employeeService->syncEmployees($company);

            return $this->successResponse(
                $result,
                "Sinkronisasi selesai: {$result['matched']} cocok, {$result['unmatched']} belum cocok dari total {$result['total']} karyawan aktif."
            );
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menyinkronkan karyawan: '.$e->getMessage(), 400);
        }
    }

    public function retrySync(Request $request, LarkAttendanceService $attendanceService): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        try {
            $result = $attendanceService->retryUnsyncedAttendances($company);

            return $this->successResponse(
                $result,
                "Pengiriman ulang selesai: {$result['synced']} berhasil dikirim, {$result['failed']} gagal, {$result['skipped']} dilewati."
            );
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengirim ulang absensi: '.$e->getMessage(), 400);
        }
    }

    public function mapping(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $search = $request->input('search');
        $filter = $request->input('filter', 'all');
        $perPage = min((int) $request->input('per_page', 20), 100);

        $query = $company->employees()
            ->where('is_active', true)
            ->with(['department:id,name', 'position:id,name']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('lark_user_id', 'like', "%{$search}%");
            });
        }

        if ($filter === 'mapped') {
            $query->whereNotNull('lark_user_id');
        } elseif ($filter === 'unmapped') {
            $query->whereNull('lark_user_id');
        }

        $paginator = $query->orderBy('first_name')->paginate($perPage);

        $paginator->getCollection()->transform(function (Employee $emp) {
            return [
                'id' => $emp->id,
                'employee_id' => $emp->employee_id,
                'name' => trim("{$emp->first_name} {$emp->last_name}"),
                'email' => $emp->email,
                'department' => $emp->department?->name,
                'position' => $emp->position?->name,
                'lark_user_id' => $emp->lark_user_id,
                'lark_open_id' => $emp->lark_open_id,
                'is_mapped' => ! empty($emp->lark_user_id),
            ];
        });

        return $this->paginatedResponse($paginator, 'Daftar mapping karyawan Lark berhasil diambil');
    }

    public function saveEmployeeMapping(Request $request, int|string $id, LarkEmployeeService $employeeService): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $employee = $company->employees()->find($id);

        if (! $employee) {
            return $this->errorResponse('Karyawan tidak ditemukan pada perusahaan ini', 404);
        }

        $validated = $request->validate([
            'lark_user_id' => ['required', 'string', 'max:100'],
            'lark_open_id' => ['nullable', 'string', 'max:100'],
        ]);

        $employeeService->mapEmployeeManually($employee, $validated['lark_user_id'], $validated['lark_open_id'] ?? null);

        return $this->successResponse([
            'id' => $employee->id,
            'lark_user_id' => $employee->lark_user_id,
            'lark_open_id' => $employee->lark_open_id,
        ], "Mapping Lark untuk {$employee->first_name} {$employee->last_name} berhasil disimpan");
    }

    public function unmapEmployee(Request $request, int|string $id, LarkEmployeeService $employeeService): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $employee = $company->employees()->find($id);

        if (! $employee) {
            return $this->errorResponse('Karyawan tidak ditemukan pada perusahaan ini', 404);
        }

        $employeeService->unmapEmployee($employee);

        return $this->successResponse([
            'id' => $employee->id,
        ], "Mapping Lark untuk {$employee->first_name} {$employee->last_name} berhasil dihapus");
    }
}
