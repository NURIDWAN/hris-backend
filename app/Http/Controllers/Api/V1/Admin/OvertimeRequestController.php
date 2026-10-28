<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Services\OvertimeCalculationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OvertimeRequestController extends AdminBaseController
{
    public function __construct(
        protected OvertimeCalculationService $calculationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = OvertimeRequest::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'approver:id,name',
        ])->where('company_id', $companyId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($overtimeType = $request->input('overtime_type')) {
            $query->where('overtime_type', $overtimeType);
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->where('date', '<=', $endDate);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $requests = $query->orderBy('date', 'desc')->paginate($perPage);

        return $this->paginatedResponse($requests, 'Daftar pengajuan lembur berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'overtime_type' => ['required', 'in:weekday,weekend,holiday'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $exists = OvertimeRequest::where('company_id', $companyId)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($exists) {
            return $this->errorResponse('Karyawan sudah memiliki pengajuan lembur pada tanggal tersebut', 422);
        }

        $employee = Employee::where('company_id', $companyId)->findOrFail($validated['employee_id']);
        $setting = OvertimeSetting::where('company_id', $companyId)->first();

        $start = Carbon::parse($validated['start_time']);
        $end = Carbon::parse($validated['end_time']);
        $overtimeHours = round($start->diffInMinutes($end) / 60, 2);

        $overtimeAmount = 0;
        if ($setting && $employee->currentSalary) {
            $calculation = $this->calculationService->calculate(
                $employee,
                $overtimeHours,
                $validated['overtime_type'],
                $setting
            );
            $overtimeAmount = $calculation['overtime_amount'] ?? 0;
        }

        $overtime = OvertimeRequest::create([
            'company_id' => $companyId,
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'overtime_hours' => $overtimeHours,
            'overtime_type' => $validated['overtime_type'],
            'overtime_amount' => $overtimeAmount,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        return $this->successResponse(
            $overtime->load(['employee:id,first_name,last_name,employee_id']),
            'Pengajuan lembur berhasil dibuat',
            201
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $overtime = OvertimeRequest::with(['employee', 'approver:id,name,email'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($overtime, 'Detail pengajuan lembur berhasil diambil');
    }

    public function approve(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $overtime = OvertimeRequest::where('company_id', $companyId)->findOrFail($id);

        if (! method_exists($overtime, 'canBeApproved') || ! $overtime->canBeApproved()) {
            if ($overtime->status !== OvertimeRequest::STATUS_PENDING) {
                return $this->errorResponse('Pengajuan lembur ini tidak dapat disetujui', 422);
            }
        }

        $user = $request->user();
        $overtime->approve($user->id, $request->input('notes'));

        return $this->successResponse($overtime->fresh(), 'Pengajuan lembur berhasil disetujui');
    }

    public function reject(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $overtime = OvertimeRequest::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! method_exists($overtime, 'canBeRejected') || ! $overtime->canBeRejected()) {
            if ($overtime->status !== OvertimeRequest::STATUS_PENDING) {
                return $this->errorResponse('Pengajuan lembur ini tidak dapat ditolak', 422);
            }
        }

        $user = $request->user();
        $overtime->reject($user->id, $validated['reason']);

        return $this->successResponse($overtime->fresh(), 'Pengajuan lembur berhasil ditolak');
    }
}
