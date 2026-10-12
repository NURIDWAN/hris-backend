<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LeaveReportController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();

        $startDate = $request->get('start_date', $companyNow->copy()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', $companyNow->copy()->endOfYear()->format('Y-m-d'));

        $query = LeaveRequest::with(['employee.department', 'leaveType'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            });

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leaveRequests = $query->orderByDesc('start_date')->get();

        $summary = $this->getSummary($companyId, $startDate, $endDate);

        $leaveTypes = LeaveType::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();

        return view('reports.leave.index', compact(
            'leaveRequests',
            'summary',
            'leaveTypes',
            'departments',
            'startDate',
            'endDate'
        ));
    }

    public function balance(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();

        $query = Employee::with(['department', 'leaveRequests' => function ($q) use ($companyNow) {
            $q->whereYear('start_date', $companyNow->year)->where('status', 'approved');
        }])
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $employees = $query->orderBy('first_name')->get();

        $leaveTypes = LeaveType::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        // Calculate balance for each employee
        $employees = $employees->map(function ($employee) use ($leaveTypes) {
            $balances = [];
            foreach ($leaveTypes as $leaveType) {
                $used = $employee->leaveRequests
                    ->where('leave_type_id', $leaveType->id)
                    ->sum('total_days');

                $balances[$leaveType->id] = [
                    'leave_type' => $leaveType,
                    'allocated' => $leaveType->default_days,
                    'used' => $used,
                    'remaining' => $leaveType->default_days - $used,
                ];
            }
            $employee->balances = $balances;

            return $employee;
        });

        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();

        return view('reports.leave.balance', compact('employees', 'leaveTypes', 'departments'));
    }

    public function byType(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();

        $startDate = $request->get('start_date', $companyNow->copy()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', $companyNow->copy()->endOfYear()->format('Y-m-d'));

        $leaveTypes = LeaveType::withCount(['leaveRequests' => function ($q) use ($companyId, $startDate, $endDate) {
            $q->where('company_id', $companyId)
                ->where('status', 'approved')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate]);
                });
        }])
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get total days for each leave type
        $leaveTypes = $leaveTypes->map(function ($leaveType) use ($companyId, $startDate, $endDate) {
            $totalDays = LeaveRequest::where('company_id', $companyId)
                ->where('leave_type_id', $leaveType->id)
                ->where('status', 'approved')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate]);
                })
                ->sum('total_days');

            $leaveType->total_days_used = $totalDays;

            return $leaveType;
        });

        return view('reports.leave.by-type', compact('leaveTypes', 'startDate', 'endDate'));
    }

    public function export(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();
        $format = $request->get('format', 'excel');

        $startDate = $request->get('start_date', $companyNow->copy()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', $companyNow->copy()->endOfYear()->format('Y-m-d'));

        $query = LeaveRequest::with(['employee.department', 'leaveType'])
            ->where('company_id', $companyId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            });

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        $leaveRequests = $query->orderByDesc('start_date')->get();

        if ($format === 'pdf') {
            $company = auth()->user()->company;
            $pdf = Pdf::loadView('reports.leave.pdf', compact('leaveRequests', 'company', 'startDate', 'endDate'));

            return $pdf->download('laporan-cuti-'.$startDate.'-'.$endDate.'.pdf');
        }

        return $this->exportExcel($leaveRequests, $startDate, $endDate);
    }

    private function getSummary(int $companyId, string $startDate, string $endDate): array
    {
        $leaveRequests = LeaveRequest::where('company_id', $companyId)
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        return [
            'total_requests' => $leaveRequests->count(),
            'approved' => $leaveRequests->where('status', 'approved')->count(),
            'pending' => $leaveRequests->where('status', 'pending')->count(),
            'rejected' => $leaveRequests->where('status', 'rejected')->count(),
            'total_days_approved' => $leaveRequests->where('status', 'approved')->sum('total_days'),
        ];
    }

    private function exportExcel($leaveRequests, $startDate, $endDate)
    {
        $filename = 'laporan-cuti-'.$startDate.'-'.$endDate.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($leaveRequests) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'No',
                'Nama Karyawan',
                'Departemen',
                'Jenis Cuti',
                'Tanggal Mulai',
                'Tanggal Selesai',
                'Jumlah Hari',
                'Status',
                'Keterangan',
            ]);

            // Data rows
            foreach ($leaveRequests as $index => $leave) {
                fputcsv($file, [
                    $index + 1,
                    $leave->employee->full_name ?? '-',
                    $leave->employee->department?->name ?? '-',
                    $leave->leaveType->name ?? '-',
                    $leave->start_date->format('d/m/Y'),
                    $leave->end_date->format('d/m/Y'),
                    $leave->total_days,
                    ucfirst($leave->status),
                    $leave->reason ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
