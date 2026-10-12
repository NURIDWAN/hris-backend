<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();

        $startDate = $request->get('start_date', $companyNow->copy()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', $companyNow->copy()->endOfMonth()->format('Y-m-d'));

        $query = Attendance::with(['employee.department'])
            ->where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $attendances = $query->orderByDesc('date')->get();

        $summary = $this->getSummary($attendances);

        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();
        $employees = Employee::where('company_id', $companyId)->where('is_active', true)->orderBy('first_name')->get();

        return view('reports.attendance.index', compact(
            'attendances',
            'summary',
            'departments',
            'employees',
            'startDate',
            'endDate'
        ));
    }

    public function daily(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $date = $request->get('date', $company->today()->format('Y-m-d'));

        $query = Attendance::with(['employee.department', 'employee.position'])
            ->where('company_id', $companyId)
            ->whereDate('date', $date);

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $attendances = $query->orderBy('clock_in')->get();

        // Get all active employees for comparison
        $activeEmployees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $summary = [
            'total_employees' => $activeEmployees,
            'present' => $attendances->where('clock_in_status', 'on_time')->count(),
            'late' => $attendances->whereIn('clock_in_status', ['late', 'very_late'])->count(),
            'absent' => $activeEmployees - $attendances->count(),
            'attendance_rate' => $activeEmployees > 0 ? round(($attendances->count() / $activeEmployees) * 100, 1) : 0,
        ];

        $departments = Department::where('company_id', $companyId)->orderBy('name')->get();

        return view('reports.attendance.daily', compact('attendances', 'summary', 'departments', 'date'));
    }

    public function lateness(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();

        $startDate = $request->get('start_date', $companyNow->copy()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', $companyNow->copy()->endOfMonth()->format('Y-m-d'));

        $lateAttendances = Attendance::with(['employee.department'])
            ->where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('clock_in_status', ['late', 'very_late'])
            ->where('late_minutes', '>', 0)
            ->orderByDesc('late_minutes')
            ->get();

        // Group by employee
        $employeeLateStats = $lateAttendances->groupBy('employee_id')->map(function ($attendances) {
            return [
                'employee' => $attendances->first()->employee,
                'total_late' => $attendances->count(),
                'total_minutes' => $attendances->sum('late_minutes'),
                'avg_minutes' => round($attendances->avg('late_minutes')),
            ];
        })->sortByDesc('total_late')->values();

        $departments = Department::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('reports.attendance.lateness', compact(
            'lateAttendances',
            'employeeLateStats',
            'departments',
            'startDate',
            'endDate'
        ));
    }

    public function export(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $companyNow = $company->now();
        $format = $request->get('format', 'excel');

        $startDate = $request->get('start_date', $companyNow->copy()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', $companyNow->copy()->endOfMonth()->format('Y-m-d'));

        $query = Attendance::with(['employee.department'])
            ->where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate]);

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        $attendances = $query->orderByDesc('date')->get();

        if ($format === 'pdf') {
            $company = auth()->user()->company;
            $pdf = Pdf::loadView('reports.attendance.pdf', compact('attendances', 'company', 'startDate', 'endDate'));

            return $pdf->download('laporan-kehadiran-'.$startDate.'-'.$endDate.'.pdf');
        }

        return $this->exportExcel($attendances, $startDate, $endDate);
    }

    private function getSummary($attendances): array
    {
        return [
            'total' => $attendances->count(),
            'present' => $attendances->where('clock_in_status', 'on_time')->count(),
            'late' => $attendances->whereIn('clock_in_status', ['late', 'very_late'])->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'total_late_minutes' => $attendances->sum('late_minutes'),
            'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
        ];
    }

    private function exportExcel($attendances, $startDate, $endDate)
    {
        $filename = 'laporan-kehadiran-'.$startDate.'-'.$endDate.'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'No',
                'Tanggal',
                'Nama Karyawan',
                'Departemen',
                'Clock In',
                'Clock Out',
                'Status',
                'Terlambat (menit)',
                'Lembur (menit)',
            ]);

            // Data rows
            foreach ($attendances as $index => $attendance) {
                fputcsv($file, [
                    $index + 1,
                    $attendance->date->format('d/m/Y'),
                    $attendance->employee->full_name ?? '-',
                    $attendance->employee->department?->name ?? '-',
                    $attendance->clock_in?->format('H:i') ?? '-',
                    $attendance->clock_out?->format('H:i') ?? '-',
                    ucfirst($attendance->clock_in_status ?? $attendance->status),
                    $attendance->late_minutes,
                    $attendance->overtime_minutes,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
