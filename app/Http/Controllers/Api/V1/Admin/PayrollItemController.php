<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\PayrollController as BladePayrollController;
use App\Models\PayrollItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollItemController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = PayrollItem::with([
            'payroll:id,name,period_month,period_year,status',
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'details',
        ])->whereHas('payroll', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });

        if ($payrollId = $request->input('payroll_id')) {
            $query->where('payroll_id', $payrollId);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('department_name', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $items = $query->orderBy('employee_name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($items, 'Daftar slip gaji berhasil diambil');
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $item = PayrollItem::with([
            'payroll',
            'employee',
            'details',
            'reimbursements',
        ])->whereHas('payroll', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->findOrFail($id);

        return $this->successResponse($item, 'Detail slip gaji berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $item = PayrollItem::with('payroll')->whereHas('payroll', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->findOrFail($id);

        if ($item->payroll->status === 'paid') {
            return $this->errorResponse('Slip gaji pada payroll yang sudah dibayar tidak dapat diedit', 422);
        }

        $validated = $request->validate([
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
            'total_earnings' => ['sometimes', 'numeric', 'min:0'],
            'total_deductions' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $basic = $validated['basic_salary'] ?? $item->basic_salary;
        $earnings = $validated['total_earnings'] ?? $item->total_earnings;
        $deductions = $validated['total_deductions'] ?? $item->total_deductions;
        $tax = $validated['tax_amount'] ?? $item->tax_amount;

        $gross = $basic + $earnings;
        $net = $gross - $deductions - $tax;

        $item->update([
            'basic_salary' => $basic,
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'gross_salary' => $gross,
            'tax_amount' => $tax,
            'net_salary' => $net,
            'notes' => $validated['notes'] ?? $item->notes,
        ]);

        return $this->successResponse($item->fresh(['details']), 'Slip gaji berhasil disesuaikan');
    }

    public function recalculate(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $item = PayrollItem::with(['payroll', 'employee'])->whereHas('payroll', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->findOrFail($id);

        if ($item->payroll->status === 'paid') {
            return $this->errorResponse('Slip gaji pada payroll yang sudah dibayar tidak dapat dihitung ulang', 422);
        }

        $bladeController = app(BladePayrollController::class);

        // Delete current item details and re-process
        $item->details()->delete();
        $payroll = $item->payroll;
        $employee = $item->employee;

        $item->delete();
        $newItem = $bladeController->processSingleEmployee($payroll, $employee);

        return $this->successResponse($newItem?->load('details'), 'Slip gaji berhasil dihitung ulang');
    }
}
