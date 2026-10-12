<?php

namespace App\Http\Controllers;

use App\Models\ThrPayment;
use App\Models\ThrSetting;
use App\Services\ThrCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThrController extends Controller
{
    public function __construct(
        protected ThrCalculationService $calculationService
    ) {}

    public function index(Request $request): View
    {
        $tenant = app('tenant');
        $year = $request->get('year', $tenant->now()->year);

        // Optimized: Use join for search instead of whereHas
        $query = ThrPayment::with(['employee.department', 'employee.position'])
            ->where('thr_payments.company_id', $tenant->id)
            ->forYear($year);

        // Search by employee name - optimized with join
        if ($request->filled('search')) {
            $search = $request->search;
            $query->join('employees', 'thr_payments.employee_id', '=', 'employees.id')
                ->where(function ($q) use ($search) {
                    $q->where('employees.first_name', 'like', "%{$search}%")
                        ->orWhere('employees.last_name', 'like', "%{$search}%")
                        ->orWhere('employees.employee_id', 'like', "%{$search}%");
                })
                ->select('thr_payments.*');
        }

        // Filter by religious holiday
        if ($request->filled('religious_holiday')) {
            $query->where('religious_holiday', $request->religious_holiday);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();
        $currentYear = $tenant->now()->year;

        return view('thr.index', [
            'payments' => $payments,
            'year' => $year,
            'years' => range($currentYear, $currentYear - 5),
        ]);
    }

    public function calculate(): View
    {
        $tenant = app('tenant');

        $setting = ThrSetting::where('company_id', $tenant->id)->first();
        $currentYear = $tenant->now()->year;

        return view('thr.calculate', [
            'setting' => $setting,
            'religiousHolidays' => ThrSetting::RELIGIOUS_HOLIDAYS,
            'years' => range($currentYear, $currentYear + 1),
        ]);
    }

    public function doCalculate(Request $request): JsonResponse
    {
        $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'religious_holiday' => ['required', 'string'],
        ]);

        $tenant = app('tenant');
        $setting = ThrSetting::where('company_id', $tenant->id)->first();

        if (! $setting) {
            return response()->json([
                'success' => false,
                'message' => 'Pengaturan THR belum dikonfigurasi.',
            ], 422);
        }

        $results = $this->calculationService->calculateForCompany($tenant->id, $setting);

        return response()->json([
            'success' => true,
            'data' => $results,
            'setting' => [
                'calculation_method' => $setting->calculation_method,
                'calculation_method_label' => $setting->calculation_method_label,
                'include_allowances' => $setting->include_allowances,
            ],
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $request->validate([
            'year' => ['required', 'integer'],
            'religious_holiday' => ['required', 'string'],
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => ['required', 'integer', 'exists:employees,id'],
            'payment_date' => ['required', 'date'],
        ]);

        $tenant = app('tenant');
        $setting = ThrSetting::where('company_id', $tenant->id)->first();

        if (! $setting) {
            return back()->with('error', 'Pengaturan THR belum dikonfigurasi.');
        }

        $year = $request->year;
        $religiousHoliday = $request->religious_holiday;
        $employeeIds = $request->employee_ids;
        $paymentDate = $request->payment_date;

        $created = 0;
        $skipped = 0;

        foreach ($employeeIds as $employeeId) {
            // Check if already exists
            $exists = ThrPayment::where('company_id', $tenant->id)
                ->where('employee_id', $employeeId)
                ->where('year', $year)
                ->where('religious_holiday', $religiousHoliday)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $employee = \App\Models\Employee::where('company_id', $tenant->id)
                ->where('id', $employeeId)
                ->first();

            if (! $employee) {
                $skipped++;

                continue;
            }
            $calculation = $this->calculationService->calculateForEmployee($employee, $setting);

            if (! ($calculation['eligible'] ?? false)) {
                $skipped++;

                continue;
            }

            ThrPayment::create([
                'company_id' => $tenant->id,
                'employee_id' => $employeeId,
                'year' => $year,
                'religious_holiday' => $religiousHoliday,
                'base_salary' => $calculation['base_salary'],
                'allowances' => $calculation['allowances'],
                'amount' => $calculation['thr_amount'],
                'service_months' => $calculation['service_months'],
                'calculation_method' => $calculation['calculation_method'],
                'status' => ThrPayment::STATUS_PENDING,
                'payment_date' => $paymentDate,
            ]);

            $created++;
        }

        if ($skipped > 0 && $created === 0) {
            return redirect()->route('thr.index')
                ->with('warning', "Semua karyawan yang dipilih sudah memiliki pembayaran THR untuk tahun {$year}.");
        }

        if ($skipped > 0) {
            return redirect()->route('thr.index')
                ->with('success', "{$created} pembayaran THR berhasil dibuat. {$skipped} dilewati karena sudah ada.");
        }

        return redirect()->route('thr.index')
            ->with('success', "{$created} pembayaran THR berhasil dibuat.");
    }

    public function pay(ThrPayment $thr): RedirectResponse
    {
        $tenant = app('tenant');

        if ($thr->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $thr->isPending()) {
            return back()->with('error', 'THR tidak dapat dibayar karena status bukan pending.');
        }

        $thr->markAsPaid();

        return back()->with('success', 'THR berhasil ditandai sudah dibayar.');
    }

    public function cancel(ThrPayment $thr): RedirectResponse
    {
        $tenant = app('tenant');

        if ($thr->company_id !== $tenant->id) {
            abort(404);
        }

        if ($thr->isPaid()) {
            return back()->with('error', 'THR yang sudah dibayar tidak dapat dibatalkan.');
        }

        $thr->cancel();

        return back()->with('success', 'THR berhasil dibatalkan.');
    }
}
