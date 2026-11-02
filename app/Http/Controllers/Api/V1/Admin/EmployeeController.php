<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeController extends AdminBaseController
{
    /**
     * List employees with filtering and search
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

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
    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $employee = Employee::with([
            'department',
            'position',
            'workSchedule',
            'officeLocations',
            'user:id,email,phone,is_active',
            'currentSalary.components.salaryComponent',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $employee,
        ]);
    }

    /**
     * Create new employee
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        // Normalize parameter aliases for frontend compatibility
        $normalizations = [];
        if ($request->has('birth_date') && ! $request->has('date_of_birth')) {
            $normalizations['date_of_birth'] = $request->input('birth_date');
        }
        if ($request->input('employment_status') === 'internship') {
            $normalizations['employment_status'] = 'intern';
        }
        if (! empty($normalizations)) {
            $request->merge($normalizations);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'position_id' => ['required', Rule::exists('positions', 'id')->where('company_id', $companyId)],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'hire_date' => ['required', 'date'],
            'employment_status' => ['required', 'in:permanent,contract,probation,intern'],
            'work_schedule_id' => ['nullable', Rule::exists('work_schedules', 'id')->where('company_id', $companyId)],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'tax_status' => ['nullable', 'string', 'max:10'],
            'bpjs_kesehatan' => ['nullable', 'string', 'max:20'],
            'bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:20'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'password' => ['nullable', 'string', 'min:8'],
            'office_location_ids' => ['nullable', 'array'],
            'office_location_ids.*' => [Rule::exists('office_locations', 'id')->where('company_id', $companyId)],
            'primary_office_id' => ['nullable', Rule::exists('office_locations', 'id')->where('company_id', $companyId)],
        ]);

        $employee = DB::transaction(function () use ($validated, $companyId, $request) {
            $user = null;
            if (! empty($validated['password']) && ! empty($validated['email'])) {
                setPermissionsTeamId($companyId);
                $fullName = trim($validated['first_name'].' '.($validated['last_name'] ?? ''));

                $user = User::create([
                    'company_id' => $companyId,
                    'name' => $fullName,
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'is_active' => true,
                ]);

                $user->assignRole('employee');
            }

            // Assign default work schedule if not specified
            $scheduleId = $validated['work_schedule_id'] ?? null;
            if (! $scheduleId) {
                $defaultSchedule = WorkSchedule::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->orderByDesc('is_default')
                    ->first();
                $scheduleId = $defaultSchedule?->id;
            }

            $employeeData = $validated;
            unset($employeeData['password'], $employeeData['office_location_ids'], $employeeData['primary_office_id']);
            $employeeData['company_id'] = $companyId;
            $employeeData['work_schedule_id'] = $scheduleId;
            $employeeData['is_active'] = true;

            if ($user) {
                $employeeData['user_id'] = $user->id;
            }

            $emp = Employee::create($employeeData);

            // Office locations
            $officeIds = $request->input('office_location_ids', []);
            $primaryOfficeId = (int) $request->input('primary_office_id');

            if (empty($officeIds)) {
                $defaultOffice = OfficeLocation::where('company_id', $companyId)
                    ->where('is_active', true)
                    ->orderByDesc('is_headquarters')
                    ->first();
                if ($defaultOffice) {
                    $officeIds = [$defaultOffice->id];
                    $primaryOfficeId = $defaultOffice->id;
                }
            }

            if (! empty($officeIds)) {
                $syncData = [];
                foreach ($officeIds as $offId) {
                    $syncData[(int) $offId] = ['is_primary' => ((int) $offId === $primaryOfficeId)];
                }
                $emp->officeLocations()->attach($syncData);
            }

            return $emp;
        });

        return $this->successResponse(
            $employee->load(['department', 'position', 'user', 'officeLocations', 'workSchedule']),
            'Karyawan berhasil ditambahkan',
            201
        );
    }

    /**
     * Update employee
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $employee = Employee::where('company_id', $companyId)->findOrFail($id);

        $emailRule = ['nullable', 'email', 'max:255'];
        if ($employee->user_id) {
            $emailRule[] = Rule::unique('users', 'email')->ignore($employee->user_id);
        } else {
            $emailRule[] = 'unique:users,email';
        }

        // Normalize parameter aliases for frontend compatibility
        $normalizations = [];
        if ($request->has('birth_date') && ! $request->has('date_of_birth')) {
            $normalizations['date_of_birth'] = $request->input('birth_date');
        }
        if ($request->input('employment_status') === 'internship') {
            $normalizations['employment_status'] = 'intern';
        }
        if (! empty($normalizations)) {
            $request->merge($normalizations);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => $emailRule,
            'phone' => ['nullable', 'string', 'max:20'],
            'department_id' => ['sometimes', 'required', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'position_id' => ['sometimes', 'required', Rule::exists('positions', 'id')->where('company_id', $companyId)],
            'manager_id' => ['nullable', Rule::exists('employees', 'id')->where('company_id', $companyId)->whereNot('id', $employee->id)],
            'hire_date' => ['sometimes', 'required', 'date'],
            'employment_status' => ['sometimes', 'required', 'in:permanent,contract,probation,intern'],
            'work_schedule_id' => ['nullable', Rule::exists('work_schedules', 'id')->where('company_id', $companyId)],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['nullable', 'date'],
            'marital_status' => ['nullable', 'in:single,married,divorced,widowed'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'tax_status' => ['nullable', 'string', 'max:10'],
            'bpjs_kesehatan' => ['nullable', 'string', 'max:20'],
            'bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:20'],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after:contract_start_date'],
            'is_active' => ['nullable', 'boolean'],
            'office_location_ids' => ['nullable', 'array'],
            'office_location_ids.*' => [Rule::exists('office_locations', 'id')->where('company_id', $companyId)],
            'primary_office_id' => ['nullable', Rule::exists('office_locations', 'id')->where('company_id', $companyId)],
        ]);

        DB::transaction(function () use ($validated, $employee, $request) {
            $employeeData = $validated;
            unset($employeeData['office_location_ids'], $employeeData['primary_office_id']);

            if ($request->has('is_active')) {
                $employeeData['is_active'] = $request->boolean('is_active');
            }

            $employee->update($employeeData);

            // Update user name/email if exists
            if ($employee->user) {
                $fullName = trim(($validated['first_name'] ?? $employee->first_name).' '.($validated['last_name'] ?? $employee->last_name));
                $userData = ['name' => $fullName];
                if (isset($validated['email'])) {
                    $userData['email'] = $validated['email'];
                }
                if ($request->has('is_active')) {
                    $userData['is_active'] = $request->boolean('is_active');
                }
                $employee->user->update($userData);
            }

            // Sync office locations if provided
            if ($request->has('office_location_ids')) {
                $officeIds = $request->input('office_location_ids', []);
                $primaryId = (int) $request->input('primary_office_id');
                $syncData = [];
                foreach ($officeIds as $offId) {
                    $syncData[(int) $offId] = ['is_primary' => ((int) $offId === $primaryId)];
                }
                $employee->officeLocations()->sync($syncData);
            }
        });

        return $this->successResponse(
            $employee->fresh(['department', 'position', 'user', 'officeLocations', 'workSchedule']),
            'Data karyawan berhasil diperbarui'
        );
    }

    /**
     * Delete / deactivate employee
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $employee = Employee::where('company_id', $companyId)->findOrFail($id);

        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                $employee->user->update(['is_active' => false]);
            }
            $employee->update(['is_active' => false]);
            $employee->delete();
        });

        return $this->successResponse(null, 'Karyawan berhasil dihapus');
    }

    /**
     * Reset password for employee's user account
     */
    public function resetPassword(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $employee = Employee::with('user')->where('company_id', $companyId)->findOrFail($id);

        if (! $employee->user) {
            return $this->errorResponse('Karyawan belum memiliki akun user tertaut', 422);
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $employee->user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return $this->successResponse(null, 'Password karyawan berhasil direset');
    }

    /**
     * Get lookup options (departments and positions)
     */
    public function options(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $departments = Department::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name', 'code')
            ->orderBy('name')
            ->get();

        $positions = Position::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'level')
            ->orderBy('name')
            ->get();

        $workSchedules = WorkSchedule::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name', 'start_time', 'end_time', 'is_default')
            ->orderBy('name')
            ->get();

        $officeLocations = OfficeLocation::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'name', 'code', 'address', 'city', 'is_headquarters')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $departments,
                'positions' => $positions,
                'work_schedules' => $workSchedules,
                'office_locations' => $officeLocations,
            ],
        ]);
    }
}
