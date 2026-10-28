<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\PayrollSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollSettingController extends AdminBaseController
{
    public function show(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $setting = PayrollSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'cycle_type' => 'monthly',
                'cutoff_day' => 25,
                'payment_day' => 1,
                'default_working_days' => 21,
                'auto_deduct_absent' => true,
                'auto_deduct_late' => true,
                'late_penalty_threshold' => 15,
                'late_penalty_amount' => 50000,
                'late_penalty_type' => 'fixed',
                'absent_deduction_type' => 'daily_rate',
                'absent_deduction_amount' => 0,
                'prorate_new_employees' => true,
                'prorate_resigned_employees' => true,
                'include_overtime_in_payroll' => true,
                'require_overtime_approval' => true,
                'include_reimbursement_in_payroll' => true,
                'is_active' => true,
            ]
        );

        return $this->successResponse($setting, 'Pengaturan siklus payroll berhasil diambil');
    }

    public function update(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $setting = PayrollSetting::firstOrCreate(['company_id' => $companyId]);

        $validated = $request->validate([
            'cutoff_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'payment_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'default_working_days' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'auto_deduct_absent' => ['nullable', 'boolean'],
            'auto_deduct_late' => ['nullable', 'boolean'],
            'late_penalty_threshold' => ['sometimes', 'integer', 'min:0'],
            'late_penalty_amount' => ['sometimes', 'numeric', 'min:0'],
            'late_penalty_type' => ['sometimes', 'in:fixed,percentage,daily_rate'],
            'absent_deduction_type' => ['sometimes', 'in:daily_rate,fixed,percentage'],
            'absent_deduction_amount' => ['sometimes', 'numeric', 'min:0'],
            'include_overtime_in_payroll' => ['nullable', 'boolean'],
            'include_reimbursement_in_payroll' => ['nullable', 'boolean'],
        ]);

        $booleans = [
            'auto_deduct_absent',
            'auto_deduct_late',
            'include_overtime_in_payroll',
            'include_reimbursement_in_payroll',
        ];

        foreach ($booleans as $field) {
            if ($request->has($field)) {
                $validated[$field] = $request->boolean($field);
            }
        }

        $setting->update($validated);

        return $this->successResponse($setting->fresh(), 'Pengaturan siklus payroll berhasil diperbarui');
    }
}
