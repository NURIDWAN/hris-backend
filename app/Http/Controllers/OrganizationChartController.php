<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationChartController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $viewMode = $request->get('view', 'department');

        $stats = [
            'total_departments' => Department::where('company_id', $tenant->id)->active()->count(),
            'total_employees' => Employee::where('company_id', $tenant->id)->active()->count(),
            'with_manager' => Employee::where('company_id', $tenant->id)->active()->whereNotNull('manager_id')->count(),
            'without_manager' => Employee::where('company_id', $tenant->id)->active()->whereNull('manager_id')->count(),
        ];

        return view('organization-chart.index', compact('viewMode', 'stats'));
    }

    public function departmentTree(): JsonResponse
    {
        $tenant = app('tenant');

        $departments = Department::with(['employees' => function ($query) {
            $query->where('is_active', true)
                ->with('position')
                ->orderBy('first_name');
        }, 'children'])
            ->where('company_id', $tenant->id)
            ->whereNull('parent_id')
            ->active()
            ->orderBy('name')
            ->get();

        $tree = $this->buildDepartmentTree($departments);

        return response()->json($tree);
    }

    public function employeeTree(): JsonResponse
    {
        $tenant = app('tenant');

        $topManagers = Employee::with(['position', 'department', 'subordinates' => function ($query) {
            $query->where('is_active', true)
                ->with(['position', 'department']);
        }])
            ->where('company_id', $tenant->id)
            ->where('is_active', true)
            ->whereNull('manager_id')
            ->orderBy('first_name')
            ->get();

        $tree = $this->buildEmployeeTree($topManagers);

        return response()->json($tree);
    }

    private function buildDepartmentTree($departments): array
    {
        $result = [];

        foreach ($departments as $department) {
            $node = [
                'id' => 'dept-'.$department->id,
                'type' => 'department',
                'name' => $department->name,
                'code' => $department->code,
                'employee_count' => $department->employees->count(),
                'children' => [],
            ];

            // Add employees as children
            foreach ($department->employees as $employee) {
                $node['children'][] = [
                    'id' => 'emp-'.$employee->id,
                    'type' => 'employee',
                    'name' => $employee->full_name,
                    'position' => $employee->position?->name ?? '-',
                    'photo' => $employee->photo,
                    'employee_id' => $employee->employee_id,
                ];
            }

            // Add child departments recursively
            if ($department->children->count() > 0) {
                $childDepartments = $this->buildDepartmentTree($department->children);
                $node['children'] = array_merge($node['children'], $childDepartments);
            }

            $result[] = $node;
        }

        return $result;
    }

    private function buildEmployeeTree($employees): array
    {
        $result = [];

        foreach ($employees as $employee) {
            $node = [
                'id' => 'emp-'.$employee->id,
                'type' => 'employee',
                'name' => $employee->full_name,
                'position' => $employee->position?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
                'photo' => $employee->photo,
                'employee_id' => $employee->employee_id,
                'subordinate_count' => $this->countAllSubordinates($employee),
                'children' => [],
            ];

            // Add subordinates recursively
            if ($employee->subordinates->count() > 0) {
                $node['children'] = $this->buildEmployeeTree($employee->subordinates);
            }

            $result[] = $node;
        }

        return $result;
    }

    private function countAllSubordinates(Employee $employee): int
    {
        $count = $employee->subordinates->count();

        foreach ($employee->subordinates as $subordinate) {
            $subordinate->load(['subordinates' => function ($query) {
                $query->where('is_active', true);
            }]);
            $count += $this->countAllSubordinates($subordinate);
        }

        return $count;
    }
}
