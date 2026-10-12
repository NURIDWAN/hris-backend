<?php

namespace App\Http\Controllers;

use App\Models\PayrollSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollSettingController extends Controller
{
    public function edit(): View
    {
        $tenant = app('tenant');

        $setting = PayrollSetting::firstOrCreate(
            ['company_id' => $tenant->id],
            [
                'cycle_type' => 'monthly',
                'cutoff_day' => 1,
                'default_working_days' => 22,
                'auto_deduct_absent' => true,
                'auto_deduct_late' => false,
                'late_penalty_threshold' => 3,
                'late_penalty_amount' => 0,
                'late_penalty_type' => 'fixed',
                'absent_deduction_type' => 'daily_rate',
                'absent_deduction_amount' => 0,
                'prorate_new_employees' => true,
                'prorate_resigned_employees' => true,
                'include_overtime_in_payroll' => true,
                'require_overtime_approval' => true,
                'include_reimbursement_in_payroll' => true,
                'payment_day' => 1,
                'is_active' => true,
            ]
        );

        return view('payroll-settings.edit', compact('setting'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'cycle_type' => ['required', 'in:monthly,bi-weekly,custom'],
            'cutoff_day' => ['required', 'integer', 'min:1', 'max:28'],
            'default_working_days' => ['required', 'integer', 'min:1', 'max:31'],
            'auto_deduct_absent' => ['boolean'],
            'auto_deduct_late' => ['boolean'],
            'late_penalty_threshold' => ['required', 'integer', 'min:1', 'max:31'],
            'late_penalty_amount' => ['required', 'numeric', 'min:0'],
            'late_penalty_type' => ['required', 'in:fixed,percentage,daily_rate'],
            'absent_deduction_type' => ['required', 'in:daily_rate,fixed,percentage'],
            'absent_deduction_amount' => ['required', 'numeric', 'min:0'],
            'prorate_new_employees' => ['boolean'],
            'prorate_resigned_employees' => ['boolean'],
            'include_overtime_in_payroll' => ['boolean'],
            'require_overtime_approval' => ['boolean'],
            'include_reimbursement_in_payroll' => ['boolean'],
            'payment_day' => ['required', 'integer', 'min:1', 'max:28'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Handle boolean checkboxes
        $validated['auto_deduct_absent'] = $request->boolean('auto_deduct_absent');
        $validated['auto_deduct_late'] = $request->boolean('auto_deduct_late');
        $validated['prorate_new_employees'] = $request->boolean('prorate_new_employees');
        $validated['prorate_resigned_employees'] = $request->boolean('prorate_resigned_employees');
        $validated['include_overtime_in_payroll'] = $request->boolean('include_overtime_in_payroll');
        $validated['require_overtime_approval'] = $request->boolean('require_overtime_approval');
        $validated['include_reimbursement_in_payroll'] = $request->boolean('include_reimbursement_in_payroll');

        $setting = PayrollSetting::where('company_id', $tenant->id)->first();

        if ($setting) {
            $setting->update($validated);
        } else {
            $validated['company_id'] = $tenant->id;
            PayrollSetting::create($validated);
        }

        return redirect()->route('payroll-settings.edit')
            ->with('success', 'Pengaturan payroll berhasil disimpan.');
    }
}
