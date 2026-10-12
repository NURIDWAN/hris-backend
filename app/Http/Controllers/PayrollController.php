<?php

namespace App\Http\Controllers;

use App\Exports\PayrollBankExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeSetting;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Models\Reimbursement;
use App\Services\OvertimeCalculationService;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollController extends Controller
{
    public function __construct(
        protected PayrollCalculationService $calculationService,
        protected OvertimeCalculationService $overtimeService
    ) {}

    public function index(Request $request)
    {
        $query = Payroll::with('createdBy')
            ->where('company_id', auth()->user()->company_id);

        if ($request->filled('year')) {
            $query->where('period_year', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('payroll_number', 'like', "%{$search}%");
            });
        }

        $payrolls = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate(10)
            ->withQueryString();

        $years = Payroll::where('company_id', auth()->user()->company_id)
            ->select('period_year')
            ->distinct()
            ->orderBy('period_year', 'desc')
            ->pluck('period_year');

        return view('payrolls.index', compact('payrolls', 'years'));
    }

    public function create()
    {
        return view('payrolls.create');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_month' => [
                'required',
                'integer',
                'min:1',
                'max:12',
            ],
            'period_year' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
            ],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Check if payroll already exists for this period (exclude cancelled)
        $exists = Payroll::where('company_id', $companyId)
            ->where('period_month', $validated['period_month'])
            ->where('period_year', $validated['period_year'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'period_month' => 'Payroll untuk periode ini sudah ada.',
            ]);
        }

        Payroll::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => 'draft',
            'created_by' => auth()->id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll berhasil dibuat.');
    }

    public function show(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $payroll->load(['items.employee', 'createdBy', 'approvedBy', 'paidBy']);

        return view('payrolls.show', compact('payroll'));
    }

    public function edit(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBeEdited()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll tidak dapat diedit.');
        }

        return view('payrolls.edit', compact('payroll'));
    }

    public function update(Request $request, Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBeEdited()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll tidak dapat diedit.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payroll->update($validated);

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll berhasil diperbarui.');
    }

    public function destroy(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBeCancelled()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll yang sudah dibayar tidak dapat dihapus.');
        }

        $payroll->delete();

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll berhasil dihapus.');
    }

    public function process(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBeCalculated()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll tidak dapat diproses.');
        }

        DB::transaction(function () use ($payroll) {
            $payroll->markAsProcessing();

            // Get all active employees with active salaries
            $employees = Employee::where('company_id', $payroll->company_id)
                ->where('is_active', true)
                ->with(['currentSalary.components.salaryComponent', 'department', 'position'])
                ->get();

            foreach ($employees as $employee) {
                if (! $employee->currentSalary) {
                    continue;
                }

                $this->processSingleEmployee($payroll, $employee);
            }

            $payroll->markAsCalculated();
        });

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll berhasil diproses.');
    }

    /**
     * Process payroll calculation for a single employee.
     */
    public function processSingleEmployee(Payroll $payroll, Employee $employee): ?PayrollItem
    {
        $employee->load(['currentSalary.components.salaryComponent', 'department', 'position']);
        $salary = $employee->currentSalary;

        if (! $salary) {
            return null;
        }

        $payrollSetting = PayrollSetting::where('company_id', $payroll->company_id)->first();
        $overtimeSetting = OvertimeSetting::where('company_id', $payroll->company_id)->first();

        // Calculate attendance data from actual records
        $attendanceData = $this->calculateAttendanceData(
            $employee,
            Carbon::parse($payroll->period_start),
            Carbon::parse($payroll->period_end)
        );

        $workingDays = $attendanceData['working_days'];
        $presentDays = $attendanceData['present_days'];
        $absentDays = $attendanceData['absent_days'];
        $lateDays = $attendanceData['late_days'];
        $leaveDays = $attendanceData['leave_days'];
        $overtimeHours = $attendanceData['overtime_hours'];

        // Get basic salary from BASIC component (HR-inputted value), fallback to employee_salaries.basic_salary
        $basicComponent = $salary->components->first(fn ($c) => $c->salaryComponent->code === 'BASIC');
        $basicSalary = $basicComponent ? (float) $basicComponent->amount : (float) $salary->basic_salary;

        // Calculate earnings and deductions from salary components
        $totalEarnings = 0;
        $totalComponentDeductions = 0;
        $taxableEarnings = 0;

        // Prepare attendance data for attendance-based components
        $attendanceForComponents = [
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'half_days' => $attendanceData['half_days'] ?? 0,
            'working_days' => $workingDays,
        ];

        // Store calculated amounts per component for details
        $componentAmounts = [];
        $additionalDetails = [];

        foreach ($salary->components as $component) {
            $salaryComponent = $component->salaryComponent;
            $amount = $component->amount;

            // Calculate attendance-based amount if applicable
            if ($salaryComponent->isAttendanceBased()) {
                $amount = $salaryComponent->calculateAttendanceBasedAmount(
                    $attendanceForComponents,
                    $component->amount
                );
            }

            $componentAmounts[$component->id] = $amount;

            // Skip BASIC component in earnings sum (already counted as $basicSalary)
            if ($salaryComponent->code === 'BASIC') {
                continue;
            }

            if ($salaryComponent->type === 'earning') {
                $totalEarnings += $amount;
                if ($salaryComponent->is_taxable) {
                    $taxableEarnings += $amount;
                }
            } else {
                $totalComponentDeductions += $amount;
            }
        }

        // === OVERTIME CALCULATION ===
        $overtimeAmount = 0;
        if ($overtimeSetting && $payrollSetting?->include_overtime_in_payroll && $overtimeHours > 0) {
            $overtimeData = $this->overtimeService->calculate(
                $employee,
                $overtimeHours,
                'weekday',
                $overtimeSetting
            );
            $overtimeAmount = $overtimeData['overtime_amount'];

            if ($overtimeAmount > 0) {
                $totalEarnings += $overtimeAmount;
                $taxableEarnings += $overtimeAmount;

                $additionalDetails[] = [
                    'name' => 'Lembur',
                    'code' => 'OVERTIME',
                    'type' => 'earning',
                    'category' => 'overtime',
                    'amount' => $overtimeAmount,
                    'is_taxable' => true,
                ];
            }
        }

        // === LATE PENALTY CALCULATION ===
        $latePenalty = 0;
        if ($payrollSetting && $payrollSetting->auto_deduct_late && $lateDays > 0) {
            if ($lateDays >= ($payrollSetting->late_penalty_threshold ?? 0)) {
                $latePenalty = $this->calculateLatePenalty(
                    $payrollSetting,
                    $lateDays,
                    $basicSalary,
                    $workingDays
                );

                if ($latePenalty > 0) {
                    $totalComponentDeductions += $latePenalty;

                    $additionalDetails[] = [
                        'name' => 'Denda Keterlambatan',
                        'code' => 'LATE-PEN',
                        'type' => 'deduction',
                        'category' => 'penalty',
                        'amount' => $latePenalty,
                        'is_taxable' => false,
                    ];
                }
            }
        }

        // === ABSENT DEDUCTION CALCULATION ===
        $absentDeduction = 0;
        if ($payrollSetting && $payrollSetting->auto_deduct_absent && $absentDays > 0) {
            $absentDeduction = $this->calculateAbsentDeduction(
                $payrollSetting,
                $absentDays,
                $basicSalary,
                $workingDays
            );

            if ($absentDeduction > 0) {
                $totalComponentDeductions += $absentDeduction;

                $additionalDetails[] = [
                    'name' => 'Potongan Absen',
                    'code' => 'ABSENT-DED',
                    'type' => 'deduction',
                    'category' => 'penalty',
                    'amount' => $absentDeduction,
                    'is_taxable' => false,
                ];
            }
        }

        // === REIMBURSEMENT CALCULATION ===
        $reimbursementAmount = 0;
        $reimbursements = collect();
        if ($payrollSetting?->include_reimbursement_in_payroll ?? true) {
            $reimbursements = Reimbursement::where('employee_id', $employee->id)
                ->where('status', Reimbursement::STATUS_APPROVED)
                ->whereNull('payroll_item_id')
                ->where('approved_at', '<=', $payroll->period_end)
                ->get();

            if ($reimbursements->count() > 0) {
                $reimbursementAmount = $reimbursements->sum('amount');
                $totalEarnings += $reimbursementAmount;

                $additionalDetails[] = [
                    'name' => 'Reimbursement',
                    'code' => 'REIMBURSE',
                    'type' => 'earning',
                    'category' => 'reimbursement',
                    'amount' => $reimbursementAmount,
                    'is_taxable' => false,
                ];
            }
        }

        $grossSalary = $basicSalary + $totalEarnings;

        // Calculate PPh21 and BPJS using the calculation service
        $calculation = $this->calculationService->calculate($employee, [
            'gross_salary' => $grossSalary,
            'basic_salary' => $basicSalary,
            'total_earnings' => $totalEarnings,
            'taxable_earnings' => $taxableEarnings,
            'total_deductions' => $totalComponentDeductions,
        ], $payroll->company_id);

        $taxAmount = $calculation['pph21'];
        $bpjsKesEmployee = $calculation['bpjs_kes_employee'];
        $bpjsTkEmployee = $calculation['bpjs_tk_employee'];

        // Total deductions = component deductions + BPJS employee portions
        $totalDeductions = $totalComponentDeductions + $bpjsKesEmployee + $bpjsTkEmployee;

        // Net salary = Gross - Deductions - PPh21
        $netSalary = $grossSalary - $totalDeductions - $taxAmount;

        // Create payroll item
        $payrollItem = PayrollItem::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $employee->id,
            'employee_salary_id' => $salary->id,
            'employee_name' => $employee->full_name,
            'employee_number' => $employee->employee_id,
            'department_name' => $employee->department?->name,
            'position_name' => $employee->position?->name,
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'leave_days' => $leaveDays,
            'overtime_hours' => $overtimeHours,
            'basic_salary' => $basicSalary,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'gross_salary' => $grossSalary,
            'tax_amount' => $taxAmount,
            'net_salary' => $netSalary,
            'payment_method' => $salary->payment_method,
            'bank_name' => $salary->bank_name,
            'bank_account_number' => $salary->bank_account_number,
            'bank_account_name' => $salary->bank_account_name,
            'status' => 'calculated',
        ]);

        // Create payroll item details for salary components
        foreach ($salary->components as $component) {
            $calculatedAmount = $componentAmounts[$component->id] ?? $component->amount;

            $payrollItem->addDetail(
                $component->salary_component_id,
                $component->salaryComponent->name,
                $component->salaryComponent->code,
                $component->salaryComponent->type,
                $component->salaryComponent->category,
                $calculatedAmount,
                $component->salaryComponent->is_taxable
            );
        }

        // Add additional details (overtime, late penalty, absent deduction, reimbursement)
        foreach ($additionalDetails as $detail) {
            $payrollItem->addDetail(
                null,
                $detail['name'],
                $detail['code'],
                $detail['type'],
                $detail['category'],
                $detail['amount'],
                $detail['is_taxable']
            );
        }

        // Link reimbursements to this payroll item
        if ($reimbursements->count() > 0) {
            foreach ($reimbursements as $reimbursement) {
                $reimbursement->markAsPaid('payroll', $payrollItem->id);
            }
        }

        // Add BPJS Kesehatan deduction detail
        if ($bpjsKesEmployee > 0) {
            $payrollItem->addDetail(
                null,
                'BPJS Kesehatan',
                'BPJS-KES',
                'deduction',
                'bpjs',
                $bpjsKesEmployee,
                false
            );
        }

        // Add BPJS JHT deduction detail
        $bpjsTkDetails = $calculation['bpjs_tk_details'];
        if (($bpjsTkDetails['jht_employee'] ?? 0) > 0) {
            $payrollItem->addDetail(
                null,
                'BPJS JHT',
                'BPJS-JHT',
                'deduction',
                'bpjs',
                $bpjsTkDetails['jht_employee'],
                false
            );
        }

        // Add BPJS JP deduction detail
        if (($bpjsTkDetails['jp_employee'] ?? 0) > 0) {
            $payrollItem->addDetail(
                null,
                'BPJS JP',
                'BPJS-JP',
                'deduction',
                'bpjs',
                $bpjsTkDetails['jp_employee'],
                false
            );
        }

        return $payrollItem;
    }

    /**
     * Calculate late penalty based on payroll settings
     */
    protected function calculateLatePenalty(PayrollSetting $setting, int $lateDays, float $basicSalary, int $workingDays): float
    {
        $penaltyAmount = (float) $setting->late_penalty_amount;

        return match ($setting->late_penalty_type) {
            'fixed' => $penaltyAmount * $lateDays,
            'percentage' => ($basicSalary * ($penaltyAmount / 100)) * $lateDays,
            'daily_rate' => ($basicSalary / $workingDays) * $lateDays,
            default => 0,
        };
    }

    /**
     * Calculate absent deduction based on payroll settings
     */
    protected function calculateAbsentDeduction(PayrollSetting $setting, int $absentDays, float $basicSalary, int $workingDays): float
    {
        $deductionAmount = (float) $setting->absent_deduction_amount;

        return match ($setting->absent_deduction_type) {
            'daily_rate' => ($basicSalary / $workingDays) * $absentDays,
            'fixed' => $deductionAmount * $absentDays,
            'percentage' => ($basicSalary * ($deductionAmount / 100)) * $absentDays,
            default => 0,
        };
    }

    public function approve(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBeApproved()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll tidak dapat disetujui.');
        }

        $payroll->approve(auth()->id());

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll berhasil disetujui.');
    }

    public function pay(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBePaid()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll tidak dapat dibayar.');
        }

        $payroll->markAsPaid(auth()->id());

        return redirect()->route('payrolls.show', $payroll)
            ->with('success', 'Payroll berhasil ditandai sebagai dibayar.');
    }

    public function cancel(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payroll->canBeCancelled()) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Payroll tidak dapat dibatalkan.');
        }

        $payroll->cancel();

        return redirect()->route('payrolls.index')
            ->with('success', 'Payroll berhasil dibatalkan.');
    }

    public function exportBank(Payroll $payroll)
    {
        if ($payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        // Only allow export for approved or paid payroll
        if (! in_array($payroll->status, ['approved', 'paid', 'calculated'])) {
            return redirect()->route('payrolls.show', $payroll)
                ->with('error', 'Export bank hanya dapat dilakukan untuk payroll yang sudah diproses/disetujui.');
        }

        $export = new PayrollBankExport($payroll);

        return Excel::download($export, $export->getFilename());
    }

    /**
     * Calculate attendance data for payroll period
     *
     * @return array{
     *     working_days: int,
     *     present_days: int,
     *     absent_days: int,
     *     late_days: int,
     *     half_days: int,
     *     leave_days: int,
     *     overtime_hours: float,
     *     total_working_minutes: int,
     *     total_late_minutes: int,
     *     total_overtime_minutes: int
     * }
     */
    protected function calculateAttendanceData(Employee $employee, Carbon $periodStart, Carbon $periodEnd): array
    {
        // Get attendance records for the period
        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get();

        // Get approved leave days for the period
        $leaveDays = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('start_date', [$periodStart, $periodEnd])
                    ->orWhereBetween('end_date', [$periodStart, $periodEnd])
                    ->orWhere(function ($q) use ($periodStart, $periodEnd) {
                        $q->where('start_date', '<=', $periodStart)
                            ->where('end_date', '>=', $periodEnd);
                    });
            })
            ->get()
            ->sum(function ($leave) use ($periodStart, $periodEnd) {
                $start = Carbon::parse($leave->start_date)->max($periodStart);
                $end = Carbon::parse($leave->end_date)->min($periodEnd);

                return $start->diffInDays($end) + 1;
            });

        // Calculate working days (based on employee's work schedule or weekly pattern)
        $workingDays = 0;
        $currentDate = $periodStart->copy();

        while ($currentDate <= $periodEnd) {
            $resolvedSchedule = $employee->resolveScheduleForDate($currentDate);
            if ($resolvedSchedule) {
                $workingDays++;
            } elseif (! $employee->workSchedule && ! $employee->hasWeeklySchedulePattern()) {
                // Default: Mon-Fri are working days when no schedule at all
                if ($currentDate->isWeekday()) {
                    $workingDays++;
                }
            }
            $currentDate->addDay();
        }

        // Aggregate attendance metrics
        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $halfDays = $attendances->where('status', 'half_day')->count();

        $totalWorkingMinutes = $attendances->sum('working_minutes');
        $totalLateMinutes = $attendances->sum('late_minutes');
        $totalOvertimeMinutes = $attendances->sum('overtime_minutes');

        // Convert overtime minutes to hours (rounded to 2 decimal places)
        $overtimeHours = round($totalOvertimeMinutes / 60, 2);

        return [
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'half_days' => $halfDays,
            'leave_days' => $leaveDays,
            'overtime_hours' => $overtimeHours,
            'total_working_minutes' => $totalWorkingMinutes,
            'total_late_minutes' => $totalLateMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
        ];
    }
}
