<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\WorkSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkScheduleController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = WorkSchedule::withCount('employees')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $schedules = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($schedules, 'Daftar jadwal kerja berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        // Normalize parameter aliases for frontend compatibility
        $normalizations = [];
        if ($request->has('time_in') && ! $request->has('start_time')) {
            $normalizations['start_time'] = $request->input('time_in');
        }
        if ($request->has('time_out') && ! $request->has('end_time')) {
            $normalizations['end_time'] = $request->input('time_out');
        }
        if ($request->has('late_tolerance_minutes') && ! $request->has('late_tolerance')) {
            $normalizations['late_tolerance'] = $request->input('late_tolerance_minutes');
        }
        // Sanitize seconds format (e.g. "08:00:00" -> "08:00")
        if (! empty($normalizations['start_time'])) {
            $normalizations['start_time'] = substr($normalizations['start_time'], 0, 5);
        } elseif ($request->has('start_time') && is_string($request->input('start_time'))) {
            $normalizations['start_time'] = substr($request->input('start_time'), 0, 5);
        }
        if (! empty($normalizations['end_time'])) {
            $normalizations['end_time'] = substr($normalizations['end_time'], 0, 5);
        } elseif ($request->has('end_time') && is_string($request->input('end_time'))) {
            $normalizations['end_time'] = substr($request->input('end_time'), 0, 5);
        }
        if (! empty($normalizations)) {
            $request->merge($normalizations);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('work_schedules', 'code')->where('company_id', $companyId),
            ],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'between:1,7'],
            'late_tolerance' => ['nullable', 'integer', 'min:0'],
            'early_leave_tolerance' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['is_default'])) {
            WorkSchedule::where('company_id', $companyId)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $schedule = WorkSchedule::create([
            ...$validated,
            'company_id' => $companyId,
            'working_days' => $validated['working_days'] ?? [1, 2, 3, 4, 5],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'is_default' => $request->boolean('is_default'),
        ]);

        return $this->successResponse($schedule, 'Jadwal kerja berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $schedule = WorkSchedule::with(['employees:id,first_name,last_name,employee_id,department_id,position_id'])
            ->withCount('employees')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($schedule, 'Detail jadwal kerja berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $schedule = WorkSchedule::where('company_id', $companyId)->findOrFail($id);

        // Normalize parameter aliases for frontend compatibility
        $normalizations = [];
        if ($request->has('time_in') && ! $request->has('start_time')) {
            $normalizations['start_time'] = $request->input('time_in');
        }
        if ($request->has('time_out') && ! $request->has('end_time')) {
            $normalizations['end_time'] = $request->input('time_out');
        }
        if ($request->has('late_tolerance_minutes') && ! $request->has('late_tolerance')) {
            $normalizations['late_tolerance'] = $request->input('late_tolerance_minutes');
        }
        // Sanitize seconds format (e.g. "08:00:00" -> "08:00")
        if (! empty($normalizations['start_time'])) {
            $normalizations['start_time'] = substr($normalizations['start_time'], 0, 5);
        } elseif ($request->has('start_time') && is_string($request->input('start_time'))) {
            $normalizations['start_time'] = substr($request->input('start_time'), 0, 5);
        }
        if (! empty($normalizations['end_time'])) {
            $normalizations['end_time'] = substr($normalizations['end_time'], 0, 5);
        } elseif ($request->has('end_time') && is_string($request->input('end_time'))) {
            $normalizations['end_time'] = substr($request->input('end_time'), 0, 5);
        }
        if (! empty($normalizations)) {
            $request->merge($normalizations);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('work_schedules', 'code')->where('company_id', $companyId)->ignore($schedule->id),
            ],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i'],
            'break_start' => ['nullable', 'date_format:H:i'],
            'break_end' => ['nullable', 'date_format:H:i'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'between:1,7'],
            'late_tolerance' => ['nullable', 'integer', 'min:0'],
            'early_leave_tolerance' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($validated['is_default'])) {
            WorkSchedule::where('company_id', $companyId)
                ->where('id', '!=', $schedule->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $schedule->update($validated);

        return $this->successResponse($schedule->fresh(), 'Jadwal kerja berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $schedule = WorkSchedule::where('company_id', $companyId)->findOrFail($id);

        if ($schedule->employees()->count() > 0) {
            return $this->errorResponse('Jadwal kerja tidak dapat dihapus karena masih digunakan oleh karyawan', 422);
        }

        $schedule->delete();

        return $this->successResponse(null, 'Jadwal kerja berhasil dihapus');
    }
}
