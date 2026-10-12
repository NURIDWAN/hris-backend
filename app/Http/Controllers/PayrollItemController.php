<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddPayrollItemDetailRequest;
use App\Http\Requests\AdjustPayrollItemRequest;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollItemDetail;
use App\Models\Reimbursement;
use App\Services\PayrollCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PayrollItemController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        $query = PayrollItem::with(['payroll', 'employee'])
            ->whereHas('payroll', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->whereHas('payroll', function ($q) use ($request) {
                $q->where('period_year', $request->year);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payrollItems = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $employees = Employee::where('company_id', $companyId)
            ->orderBy('first_name')
            ->get();

        $years = PayrollItem::whereHas('payroll', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })
            ->join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->select('payrolls.period_year')
            ->distinct()
            ->orderByDesc('period_year')
            ->pluck('period_year');

        return view('payroll-items.index', compact('payrollItems', 'employees', 'years'));
    }

    public function show(PayrollItem $payrollItem): View
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $payrollItem->load(['payroll', 'employee', 'details']);

        return view('payroll-items.show', compact('payrollItem'));
    }

    public function edit(PayrollItem $payrollItem): View|RedirectResponse
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payrollItem->payroll->canBeAdjusted()) {
            return redirect()
                ->route('payroll-items.show', $payrollItem)
                ->with('error', 'Slip gaji hanya bisa di-adjust saat status payroll "Terhitung".');
        }

        $payrollItem->load(['payroll', 'employee', 'details']);

        $autoCategories = ['bpjs', 'tax', 'insurance'];

        return view('payroll-items.edit', compact('payrollItem', 'autoCategories'));
    }

    public function update(AdjustPayrollItemRequest $request, PayrollItem $payrollItem): RedirectResponse
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payrollItem->payroll->canBeAdjusted()) {
            return redirect()
                ->route('payroll-items.show', $payrollItem)
                ->with('error', 'Slip gaji hanya bisa di-adjust saat status payroll "Terhitung".');
        }

        $autoCategories = ['bpjs', 'tax', 'insurance'];

        DB::transaction(function () use ($request, $payrollItem, $autoCategories) {
            $payrollItem->basic_salary = $request->basic_salary;

            // Update existing detail amounts (only non-auto)
            if ($request->has('details')) {
                foreach ($request->details as $detailData) {
                    $detail = PayrollItemDetail::find($detailData['id']);
                    if ($detail && $detail->payroll_item_id === $payrollItem->id && ! in_array($detail->category, $autoCategories)) {
                        $detail->update(['amount' => $detailData['amount']]);
                    }
                }
            }

            $this->recalculateWithAutoComponents($payrollItem);
        });

        return redirect()
            ->route('payroll-items.show', $payrollItem)
            ->with('success', 'Slip gaji berhasil di-adjust.');
    }

    public function addDetail(AddPayrollItemDetailRequest $request, PayrollItem $payrollItem): RedirectResponse
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payrollItem->payroll->canBeAdjusted()) {
            return redirect()
                ->route('payroll-items.edit', $payrollItem)
                ->with('error', 'Slip gaji hanya bisa di-adjust saat status payroll "Terhitung".');
        }

        DB::transaction(function () use ($request, $payrollItem) {
            $payrollItem->addDetail(
                componentId: null,
                name: $request->component_name,
                code: null,
                type: $request->type,
                category: $request->category,
                amount: $request->amount,
                isTaxable: $request->is_taxable ?? true,
                notes: $request->notes,
            );

            $this->recalculateWithAutoComponents($payrollItem);
        });

        return redirect()
            ->route('payroll-items.edit', $payrollItem)
            ->with('success', 'Komponen berhasil ditambahkan.');
    }

    public function removeDetail(PayrollItem $payrollItem, PayrollItemDetail $detail): RedirectResponse
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payrollItem->payroll->canBeAdjusted()) {
            return redirect()
                ->route('payroll-items.edit', $payrollItem)
                ->with('error', 'Slip gaji hanya bisa di-adjust saat status payroll "Terhitung".');
        }

        $autoCategories = ['bpjs', 'tax', 'insurance'];

        if (in_array($detail->category, $autoCategories)) {
            return redirect()
                ->route('payroll-items.edit', $payrollItem)
                ->with('error', 'Komponen otomatis (BPJS/PPh21) tidak dapat dihapus.');
        }

        if ($detail->payroll_item_id !== $payrollItem->id) {
            abort(404);
        }

        DB::transaction(function () use ($payrollItem, $detail) {
            $detail->delete();

            $this->recalculateWithAutoComponents($payrollItem);
        });

        return redirect()
            ->route('payroll-items.edit', $payrollItem)
            ->with('success', 'Komponen berhasil dihapus.');
    }

    public function recalculate(PayrollItem $payrollItem): RedirectResponse
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        if (! $payrollItem->payroll->canBeAdjusted()) {
            return redirect()
                ->route('payroll-items.show', $payrollItem)
                ->with('error', 'Slip gaji hanya bisa dihitung ulang saat status payroll "Terhitung".');
        }

        $payroll = $payrollItem->payroll;
        $employee = $payrollItem->employee;

        if (! $employee) {
            return redirect()
                ->route('payroll-items.show', $payrollItem)
                ->with('error', 'Data karyawan tidak ditemukan.');
        }

        DB::transaction(function () use ($payrollItem, $payroll, $employee) {
            // Unlink reimbursements
            Reimbursement::where('payroll_item_id', $payrollItem->id)
                ->update(['payroll_item_id' => null, 'payment_method' => null, 'status' => 'approved']);

            // Delete existing details and force-delete the item
            $payrollItem->details()->delete();
            $payrollItem->forceDelete();

            // Re-process this single employee using the PayrollController's process logic
            app(PayrollController::class)->processSingleEmployee($payroll, $employee);

            // Recalculate payroll totals
            $payroll->recalculateTotals();
            $payroll->save();
        });

        // Find the newly created payroll item for this employee
        $newItem = PayrollItem::where('payroll_id', $payroll->id)
            ->where('employee_id', $employee->id)
            ->first();

        return redirect()
            ->route('payroll-items.show', $newItem ?? $payrollItem)
            ->with('success', 'Slip gaji berhasil dihitung ulang.');
    }

    public function pdf(PayrollItem $payrollItem)
    {
        if ($payrollItem->payroll->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $payrollItem->load(['payroll', 'employee', 'details']);

        $company = auth()->user()->company;

        $pdf = Pdf::loadView('payroll-items.pdf', compact('payrollItem', 'company'));

        $filename = 'slip-gaji-'.$payrollItem->employee_number.'-'.$payrollItem->payroll->period_label.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * Recalculate payroll item with auto-components (BPJS, PPh21).
     */
    protected function recalculateWithAutoComponents(PayrollItem $payrollItem): void
    {
        $payrollItem->load('employee');
        $companyId = $payrollItem->payroll->company_id;

        // Sum earnings excluding basic salary (to avoid double-counting)
        $totalEarnings = $payrollItem->details()
            ->where('type', 'earning')
            ->where(fn ($q) => $q->whereNull('component_code')->orWhere('component_code', '!=', 'BASIC'))
            ->sum('amount');

        $payrollItem->total_earnings = $totalEarnings;
        $payrollItem->gross_salary = $payrollItem->basic_salary + $totalEarnings;

        // Remove old auto-calculated deductions
        $autoCategories = ['bpjs', 'tax', 'insurance'];
        $payrollItem->details()
            ->where('type', 'deduction')
            ->whereIn('category', $autoCategories)
            ->delete();

        // Recalculate BPJS & PPh21
        $calculationService = app(PayrollCalculationService::class);
        $deductionBreakdown = $calculationService->getDeductionBreakdown(
            $payrollItem->employee,
            (float) $payrollItem->gross_salary,
            $companyId,
        );

        // Re-add BPJS deduction details
        foreach (['bpjs_kes', 'bpjs_jht', 'bpjs_jp'] as $key) {
            if (isset($deductionBreakdown[$key]) && $deductionBreakdown[$key]['amount'] > 0) {
                $item = $deductionBreakdown[$key];
                $payrollItem->addDetail(
                    componentId: null,
                    name: $item['name'],
                    code: $item['code'],
                    type: 'deduction',
                    category: 'bpjs',
                    amount: $item['amount'],
                    isTaxable: false,
                );
            }
        }

        // Update tax_amount from PPh21
        $payrollItem->tax_amount = $deductionBreakdown['pph21']['amount'] ?? 0;

        // Recalculate total deductions (all deduction details)
        $payrollItem->total_deductions = $payrollItem->details()
            ->where('type', 'deduction')
            ->sum('amount');

        // Net salary = gross - deductions - tax
        $payrollItem->net_salary = $payrollItem->gross_salary - $payrollItem->total_deductions - $payrollItem->tax_amount;
        $payrollItem->save();

        // Update parent payroll totals
        $payroll = $payrollItem->payroll;
        $payroll->recalculateTotals();
        $payroll->save();
    }
}
