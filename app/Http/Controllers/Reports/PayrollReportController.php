<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PayrollReportController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;

        $year = $request->get('year', $company->now()->year);
        $month = $request->get('month');

        $query = Payroll::with(['items.employee.department'])
            ->where('company_id', $companyId)
            ->where('period_year', $year);

        if ($month) {
            $query->where('period_month', $month);
        }

        $payrolls = $query->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->get();

        $summary = $this->getSummary($companyId, $year, $month);

        return view('reports.payroll.index', compact('payrolls', 'summary', 'year', 'month'));
    }

    public function byDepartment(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();

        $year = $request->get('year', $companyNow->year);
        $month = $request->get('month', $companyNow->month);

        $departments = Department::where('company_id', $companyId)
            ->with(['employees' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        // Get payroll for selected period
        $payroll = Payroll::where('company_id', $companyId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        // Calculate per department totals
        $departments = $departments->map(function ($department) use ($payroll) {
            if ($payroll) {
                $payrollItems = PayrollItem::where('payroll_id', $payroll->id)
                    ->whereHas('employee', function ($q) use ($department) {
                        $q->where('department_id', $department->id);
                    })
                    ->get();

                $department->total_gross = $payrollItems->sum('gross_salary');
                $department->total_deductions = $payrollItems->sum('total_deductions');
                $department->total_net = $payrollItems->sum('net_salary');
                $department->employee_count = $payrollItems->count();
            } else {
                $department->total_gross = 0;
                $department->total_deductions = 0;
                $department->total_net = 0;
                $department->employee_count = 0;
            }

            return $department;
        });

        return view('reports.payroll.by-department', compact('departments', 'year', 'month', 'payroll'));
    }

    public function taxSummary(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;

        $year = $request->get('year', $company->now()->year);

        $payrolls = Payroll::where('company_id', $companyId)
            ->where('period_year', $year)
            ->whereIn('status', ['approved', 'paid'])
            ->orderBy('period_month')
            ->get();

        // Calculate monthly tax totals
        $monthlyTax = [];
        for ($i = 1; $i <= 12; $i++) {
            $payroll = $payrolls->where('period_month', $i)->first();
            $monthlyTax[$i] = [
                'month' => $i,
                'total_pph21' => $payroll ? PayrollItem::where('payroll_id', $payroll->id)->sum('tax_amount') : 0,
                'total_gross' => $payroll ? PayrollItem::where('payroll_id', $payroll->id)->sum('gross_salary') : 0,
                'employee_count' => $payroll ? PayrollItem::where('payroll_id', $payroll->id)->count() : 0,
            ];
        }

        $grandTotal = [
            'total_pph21' => array_sum(array_column($monthlyTax, 'total_pph21')),
            'total_gross' => array_sum(array_column($monthlyTax, 'total_gross')),
        ];

        return view('reports.payroll.tax-summary', compact('monthlyTax', 'grandTotal', 'year'));
    }

    public function export(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();
        $format = $request->get('format', 'excel');

        $year = $request->get('year', $companyNow->year);
        $month = $request->get('month', $companyNow->month);

        $payroll = Payroll::where('company_id', $companyId)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->first();

        $payrollItems = collect();
        if ($payroll) {
            $payrollItems = PayrollItem::with(['employee.department', 'employee.position'])
                ->where('payroll_id', $payroll->id)
                ->get();
        }

        if ($format === 'pdf') {
            $company = auth()->user()->company;
            $pdf = Pdf::loadView('reports.payroll.pdf', compact('payrollItems', 'company', 'year', 'month', 'payroll'));

            return $pdf->download('laporan-penggajian-'.$year.'-'.$month.'.pdf');
        }

        return $this->exportExcel($payrollItems, $year, $month);
    }

    private function getSummary(int $companyId, int $year, ?int $month): array
    {
        $query = Payroll::where('company_id', $companyId)
            ->where('period_year', $year);

        if ($month) {
            $query->where('period_month', $month);
        }

        $payrolls = $query->get();
        $payrollIds = $payrolls->pluck('id');

        $totalGross = PayrollItem::whereIn('payroll_id', $payrollIds)->sum('gross_salary');
        $totalDeductions = PayrollItem::whereIn('payroll_id', $payrollIds)->sum('total_deductions');
        $totalNet = PayrollItem::whereIn('payroll_id', $payrollIds)->sum('net_salary');
        $totalPph21 = PayrollItem::whereIn('payroll_id', $payrollIds)->sum('tax_amount');
        $employeeCount = PayrollItem::whereIn('payroll_id', $payrollIds)->distinct('employee_id')->count('employee_id');

        return [
            'total_payrolls' => $payrolls->count(),
            'total_gross' => $totalGross,
            'total_deductions' => $totalDeductions,
            'total_net' => $totalNet,
            'total_pph21' => $totalPph21,
            'employee_count' => $employeeCount,
            'paid' => $payrolls->where('status', 'paid')->count(),
            'pending' => $payrolls->whereIn('status', ['draft', 'processed', 'approved'])->count(),
        ];
    }

    private function exportExcel($payrollItems, $year, $month)
    {
        $filename = 'laporan-penggajian-'.$year.'-'.$month.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($payrollItems) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'No',
                'Nama Karyawan',
                'Departemen',
                'Jabatan',
                'Gaji Pokok',
                'Pendapatan',
                'Gaji Kotor',
                'Potongan',
                'Pajak',
                'Gaji Bersih',
            ]);

            // Data rows
            foreach ($payrollItems as $index => $item) {
                fputcsv($file, [
                    $index + 1,
                    $item->employee->full_name ?? '-',
                    $item->employee->department?->name ?? '-',
                    $item->employee->position?->name ?? '-',
                    $item->basic_salary,
                    $item->total_earnings,
                    $item->gross_salary,
                    $item->total_deductions,
                    $item->tax_amount,
                    $item->net_salary,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
