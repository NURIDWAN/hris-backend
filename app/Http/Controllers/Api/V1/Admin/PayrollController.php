<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\PayrollController as BladePayrollController;
use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Payroll::with('createdBy:id,name,email')
            ->where('company_id', $companyId);

        if ($year = $request->input('year')) {
            $query->where('period_year', (int) $year);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('payroll_number', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $payrolls = $query->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($payrolls, 'Daftar payroll berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $exists = Payroll::where('company_id', $companyId)
            ->where('period_month', $validated['period_month'])
            ->where('period_year', $validated['period_year'])
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($exists) {
            return $this->errorResponse('Payroll untuk periode ini sudah ada', 422);
        }

        $payroll = Payroll::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'status' => 'draft',
            'created_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->successResponse($payroll, 'Payroll periode berhasil dibuat', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $payroll = Payroll::with([
            'createdBy:id,name,email',
            'approvedBy:id,name,email',
            'paidBy:id,name,email',
            'items.employee:id,first_name,last_name,employee_id,department_id,position_id',
            'items.employee.department:id,name',
            'items.employee.position:id,name',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse([
            'payroll' => $payroll,
            'summary' => $this->calculatePayrollSummary($payroll),
        ], 'Detail payroll berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::where('company_id', $companyId)->findOrFail($id);

        $cannotEdit = (! method_exists($payroll, 'canBeEdited') || ! $payroll->canBeEdited())
            && $payroll->status !== 'draft';

        if ($cannotEdit) {
            return $this->errorResponse('Hanya payroll berstatus draft yang dapat diedit', 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'period_start' => ['sometimes', 'required', 'date'],
            'period_end' => ['sometimes', 'required', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payroll->update($validated);

        return $this->successResponse($payroll->fresh(), 'Payroll berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::where('company_id', $companyId)->findOrFail($id);

        if (! in_array($payroll->status, ['draft', 'cancelled'], true)) {
            return $this->errorResponse('Hanya payroll berstatus draft atau cancelled yang dapat dihapus', 422);
        }

        DB::transaction(function () use ($payroll) {
            $payroll->items()->delete();
            $payroll->delete();
        });

        return $this->successResponse(null, 'Payroll berhasil dihapus');
    }

    public function process(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::where('company_id', $companyId)->findOrFail($id);

        if (! in_array($payroll->status, ['draft', 'processing', 'calculated'], true)) {
            return $this->errorResponse('Payroll tidak dapat diproses (status saat ini: '.$payroll->status.')', 422);
        }

        $calculatedCount = $this->calculatePayrollItems($payroll, $companyId);

        return $this->successResponse([
            'payroll' => $payroll->fresh('items'),
            'processed_employees' => $calculatedCount,
        ], "Payroll berhasil dihitung untuk {$calculatedCount} karyawan");
    }

    public function approve(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::where('company_id', $companyId)->findOrFail($id);

        $cannotApprove = (! method_exists($payroll, 'canBeApproved') || ! $payroll->canBeApproved())
            && $payroll->status !== 'calculated';

        if ($cannotApprove) {
            return $this->errorResponse('Hanya payroll yang sudah dihitung (calculated) yang dapat disetujui', 422);
        }

        $payroll->approve($request->user()->id);

        return $this->successResponse($payroll->fresh(), 'Payroll berhasil disetujui');
    }

    public function pay(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::where('company_id', $companyId)->findOrFail($id);

        $cannotPay = (! method_exists($payroll, 'canBePaid') || ! $payroll->canBePaid())
            && $payroll->status !== 'approved';

        if ($cannotPay) {
            return $this->errorResponse('Hanya payroll yang sudah disetujui (approved) yang dapat ditandai lunas', 422);
        }

        $payroll->markAsPaid($request->user()->id);

        return $this->successResponse($payroll->fresh(), 'Payroll berhasil ditandai sebagai dibayar');
    }

    public function cancel(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::where('company_id', $companyId)->findOrFail($id);

        if ($payroll->status === 'paid') {
            return $this->errorResponse('Payroll yang sudah dibayar tidak dapat dibatalkan', 422);
        }

        $payroll->cancel($request->user()->id);

        return $this->successResponse($payroll->fresh(), 'Payroll berhasil dibatalkan');
    }

    public function exportBank(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $payroll = Payroll::with(['items.employee'])->where('company_id', $companyId)->findOrFail($id);

        $transferList = $payroll->items->map(fn ($item) => [
            'employee_id' => $item->employee_number,
            'employee_name' => $item->employee_name,
            'bank_name' => $item->bank_name,
            'bank_account_number' => $item->bank_account_number,
            'bank_account_name' => $item->bank_account_name,
            'net_salary' => (float) $item->net_salary,
            'payment_method' => $item->payment_method,
        ]);

        return $this->successResponse([
            'payroll_name' => $payroll->name,
            'period' => $payroll->period_year.'-'.str_pad($payroll->period_month, 2, '0', STR_PAD_LEFT),
            'total_transfers' => $transferList->count(),
            'total_amount' => (float) $transferList->sum('net_salary'),
            'transfers' => $transferList,
        ], 'Data transfer bank berhasil diexport');
    }

    /**
     * Compute financial totals and employee count for payroll
     *
     * @return array<string, int|float>
     */
    private function calculatePayrollSummary(Payroll $payroll): array
    {
        return [
            'total_employees' => $payroll->items->count(),
            'total_basic_salary' => (float) $payroll->items->sum('basic_salary'),
            'total_gross_salary' => (float) $payroll->items->sum('gross_salary'),
            'total_deductions' => (float) $payroll->items->sum('total_deductions'),
            'total_tax' => (float) $payroll->items->sum('tax_amount'),
            'total_net_salary' => (float) $payroll->items->sum('net_salary'),
        ];
    }

    /**
     * Calculate and persist payroll items for active employees within a transaction
     */
    private function calculatePayrollItems(Payroll $payroll, int|string $companyId): int
    {
        return DB::transaction(function () use ($payroll, $companyId) {
            $payroll->markAsProcessing();
            $payroll->items()->delete();

            $employees = Employee::where('company_id', $companyId)
                ->where('is_active', true)
                ->with(['currentSalary.components.salaryComponent', 'department', 'position'])
                ->get();

            $bladeController = app(BladePayrollController::class);
            $calculatedCount = 0;

            foreach ($employees as $employee) {
                if (! $employee->currentSalary) {
                    continue;
                }

                $item = $bladeController->processSingleEmployee($payroll, $employee);
                if ($item) {
                    $calculatedCount++;
                }
            }

            $payroll->markAsCalculated();

            return $calculatedCount;
        });
    }
}
