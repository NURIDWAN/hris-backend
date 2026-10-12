<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $query = Employee::with(['department', 'position'])
            ->where('company_id', $companyId);

        // Apply filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('first_name')->get();

        // Get summary
        $summary = $this->getSummary($companyId);

        // Get departments for filter
        $departments = Department::where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        return view('reports.employees.index', compact('employees', 'summary', 'departments'));
    }

    public function export(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $format = $request->get('format', 'excel');

        $query = Employee::with(['department', 'position'])
            ->where('company_id', $companyId);

        // Apply filters
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('employment_status')) {
            $query->where('employment_status', $request->employment_status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === '1');
        }

        $employees = $query->orderBy('first_name')->get();

        if ($format === 'pdf') {
            $company = auth()->user()->company;
            $pdf = Pdf::loadView('reports.employees.pdf', compact('employees', 'company'));
            return $pdf->download('laporan-karyawan-' . now()->format('Y-m-d') . '.pdf');
        }

        // Excel export
        return $this->exportExcel($employees);
    }

    public function byDepartment()
    {
        $companyId = auth()->user()->company_id;

        $departments = Department::withCount(['employees' => function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        }])
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get();

        $summary = $this->getSummary($companyId);

        return view('reports.employees.by-department', compact('departments', 'summary'));
    }

    private function getSummary(int $companyId): array
    {
        $employees = Employee::where('company_id', $companyId)->get();

        return [
            'total' => $employees->count(),
            'active' => $employees->where('is_active', true)->count(),
            'inactive' => $employees->where('is_active', false)->count(),
            'permanent' => $employees->where('employment_status', 'permanent')->count(),
            'contract' => $employees->where('employment_status', 'contract')->count(),
            'probation' => $employees->where('employment_status', 'probation')->count(),
        ];
    }

    private function exportExcel($employees)
    {
        $filename = 'laporan-karyawan-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'No',
                'ID Karyawan',
                'Nama',
                'Email',
                'Departemen',
                'Jabatan',
                'Status Kerja',
                'Tanggal Bergabung',
                'Status',
            ]);

            // Data rows
            foreach ($employees as $index => $employee) {
                fputcsv($file, [
                    $index + 1,
                    $employee->employee_id,
                    $employee->full_name,
                    $employee->email,
                    $employee->department?->name ?? '-',
                    $employee->position?->name ?? '-',
                    ucfirst($employee->employment_status),
                    $employee->hire_date?->format('d/m/Y') ?? '-',
                    $employee->is_active ? 'Aktif' : 'Tidak Aktif',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
