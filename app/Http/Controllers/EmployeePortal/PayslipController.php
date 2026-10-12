<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PayslipController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $payslips = PayrollItem::with(['payroll', 'employee'])
            ->where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) {
                $query->where('status', 'paid');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('portal.payslips.index', compact('employee', 'payslips'));
    }

    public function show(PayrollItem $payrollItem): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($payrollItem->employee_id !== $employee->id) {
            abort(403, 'Access denied.');
        }

        $payrollItem->load(['payroll', 'employee.department', 'employee.position']);

        return view('portal.payslips.show', compact('payrollItem', 'employee'));
    }

    public function pdf(PayrollItem $payrollItem): Response
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($payrollItem->employee_id !== $employee->id) {
            abort(403, 'Access denied.');
        }

        $payrollItem->load(['payroll', 'employee.department', 'employee.position', 'employee.company']);

        $pdf = Pdf::loadView('portal.payslips.pdf', [
            'payrollItem' => $payrollItem,
            'employee' => $payrollItem->employee,
        ]);

        return $pdf->download("slip-gaji-{$payrollItem->employee->employee_id}-{$payrollItem->payroll->period_end->format('Y-m')}.pdf");
    }
}
