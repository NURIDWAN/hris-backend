<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LarkIntegration;
use App\Services\Lark\LarkAttendanceService;
use App\Services\Lark\LarkAuthService;
use App\Services\Lark\LarkEmployeeService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LarkIntegrationController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;
        $integration = $company->larkIntegration ?? new LarkIntegration([
            'is_active' => false,
            'push_clock_in' => true,
            'push_clock_out' => true,
            'enable_bot_notifications' => false,
        ]);

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

        return view('settings.lark.index', compact('company', 'integration', 'stats'));
    }

    public function update(Request $request): RedirectResponse
    {
        $company = auth()->user()->company;
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

        // If no integration yet, app_secret is required
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
        } else {
            $company->larkIntegration()->create($data);
        }

        return redirect()->route('settings.lark.index')
            ->with('success', 'Pengaturan integrasi Lark berhasil disimpan.');
    }

    public function testConnection(Request $request, LarkAuthService $authService): RedirectResponse
    {
        $company = auth()->user()->company;
        $integration = $company->larkIntegration;

        $appId = $request->input('app_id') ?: $integration?->app_id;
        $appSecret = $request->input('app_secret') ?: $integration?->app_secret;

        if (empty($appId) || empty($appSecret)) {
            return redirect()->route('settings.lark.index')
                ->with('error', 'App ID dan App Secret wajib diisi untuk menguji koneksi.');
        }

        $result = $authService->testCredentials($appId, $appSecret);

        if ($result['success']) {
            return redirect()->route('settings.lark.index')
                ->with('success', $result['message']);
        }

        return redirect()->route('settings.lark.index')
            ->with('error', $result['message']);
    }

    public function mapping(Request $request): View
    {
        $company = auth()->user()->company;
        $search = $request->input('search');
        $filter = $request->input('filter', 'all'); // all, mapped, unmapped

        $query = $company->employees()
            ->where('is_active', true)
            ->with('department');

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

        $employees = $query->orderBy('first_name')->paginate(20)->withQueryString();

        return view('settings.lark.mapping', compact('company', 'employees', 'search', 'filter'));
    }

    public function saveEmployeeMapping(Request $request, Employee $employee, LarkEmployeeService $employeeService): RedirectResponse
    {
        // Ensure employee belongs to current user's company
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'lark_user_id' => ['required', 'string', 'max:100'],
            'lark_open_id' => ['nullable', 'string', 'max:100'],
        ]);

        $employeeService->mapEmployeeManually($employee, $validated['lark_user_id'], $validated['lark_open_id'] ?? null);

        return back()->with('success', "Mapping Lark untuk {$employee->first_name} {$employee->last_name} berhasil disimpan.");
    }

    public function unmapEmployee(Employee $employee, LarkEmployeeService $employeeService): RedirectResponse
    {
        if ($employee->company_id !== auth()->user()->company_id) {
            abort(403);
        }

        $employeeService->unmapEmployee($employee);

        return back()->with('success', "Mapping Lark untuk {$employee->first_name} {$employee->last_name} berhasil dihapus.");
    }

    public function syncEmployees(LarkEmployeeService $employeeService): RedirectResponse
    {
        $company = auth()->user()->company;

        try {
            $result = $employeeService->syncEmployees($company);

            return redirect()->route('settings.lark.index')
                ->with('success', "Sinkronisasi selesai: {$result['matched']} cocok, {$result['unmatched']} belum cocok dari total {$result['total']} karyawan aktif.");
        } catch (Exception $e) {
            return redirect()->route('settings.lark.index')
                ->with('error', 'Gagal menyinkronkan karyawan: '.$e->getMessage());
        }
    }

    public function retrySync(LarkAttendanceService $attendanceService): RedirectResponse
    {
        $company = auth()->user()->company;

        try {
            $result = $attendanceService->retryUnsyncedAttendances($company);

            return redirect()->route('settings.lark.index')
                ->with('success', "Pengiriman ulang selesai: {$result['synced']} berhasil dikirim, {$result['failed']} gagal, {$result['skipped']} dilewati.");
        } catch (Exception $e) {
            return redirect()->route('settings.lark.index')
                ->with('error', 'Gagal mengirim ulang absensi: '.$e->getMessage());
        }
    }
}
