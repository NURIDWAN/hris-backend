<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\OvertimeSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OvertimeSettingController extends AdminBaseController
{
    public function show(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $setting = OvertimeSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'weekday_rate_first_hour' => 1.5,
                'weekday_rate_next_hours' => 2.0,
                'weekend_rate' => 2.0,
                'holiday_rate' => 3.0,
                'max_overtime_hours_per_day' => 4,
                'max_overtime_hours_per_week' => 14,
                'min_overtime_minutes' => 30,
                'working_hours_per_month' => 173,
                'requires_approval' => true,
                'auto_calculate_from_attendance' => false,
                'is_active' => true,
            ]
        );

        return $this->successResponse($setting, 'Pengaturan lembur berhasil diambil');
    }

    public function update(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $setting = OvertimeSetting::firstOrCreate(['company_id' => $companyId]);

        $validated = $request->validate([
            'weekday_rate_first_hour' => ['sometimes', 'numeric', 'min:1'],
            'weekday_rate_next_hours' => ['sometimes', 'numeric', 'min:1'],
            'weekend_rate' => ['sometimes', 'numeric', 'min:1'],
            'holiday_rate' => ['sometimes', 'numeric', 'min:1'],
            'max_overtime_hours_per_day' => ['sometimes', 'integer', 'min:1', 'max:24'],
            'max_overtime_hours_per_week' => ['sometimes', 'integer', 'min:1', 'max:168'],
            'min_overtime_minutes' => ['sometimes', 'integer', 'min:0'],
            'working_hours_per_month' => ['sometimes', 'integer', 'min:1'],
            'requires_approval' => ['nullable', 'boolean'],
            'auto_calculate_from_attendance' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('requires_approval')) {
            $validated['requires_approval'] = $request->boolean('requires_approval');
        }
        if ($request->has('auto_calculate_from_attendance')) {
            $validated['auto_calculate_from_attendance'] = $request->boolean('auto_calculate_from_attendance');
        }
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $setting->update($validated);

        return $this->successResponse($setting->fresh(), 'Pengaturan lembur berhasil diperbarui');
    }
}
