<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * List employees with filtering and search
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?: Company::value('id');

        $query = Employee::with(['department:id,name,code', 'position:id,name,code', 'user:id,email,is_active'])
            ->where('company_id', $companyId);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by Department
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        // Filter by Status
        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $employees = $query->orderBy('first_name', 'asc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $employees->items(),
            'meta' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
            ],
        ]);
    }

    /**
     * Get employee detail
     */
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?: Company::value('id');

        $employee = Employee::with([
            'department',
            'position',
            'workSchedule',
            'officeLocations',
            'user:id,email,phone,is_active',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * Get lookup options (departments and positions)
     */
    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id ?: Company::value('id');

        $departments = Department::where('company_id', $companyId)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $positions = Position::where('company_id', $companyId)
            ->select('id', 'name', 'code', 'level')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'positions' => $positions,
            ],
        ]);
    }
}
