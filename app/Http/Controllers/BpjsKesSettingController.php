<?php

namespace App\Http\Controllers;

use App\Models\BpjsKesSetting;
use Illuminate\Http\Request;

class BpjsKesSettingController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $currentYear = date('Y');

        $setting = BpjsKesSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'company_rate' => 4.00,
                'employee_rate' => 1.00,
                'min_salary_basis' => 2900000,
                'max_salary_basis' => 12000000,
                'effective_year' => $currentYear,
                'is_active' => true,
            ]
        );

        return view('bpjs-kes-settings.index', compact('setting', 'currentYear'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_rate' => 'required|numeric|min:0|max:100',
            'employee_rate' => 'required|numeric|min:0|max:100',
            'min_salary_basis' => 'required|numeric|min:0',
            'max_salary_basis' => 'required|numeric|min:0',
            'effective_year' => 'required|integer|min:2020|max:2030',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = auth()->user()->company_id;

        BpjsKesSetting::updateOrCreate(
            ['company_id' => $companyId],
            [
                'company_rate' => $request->company_rate,
                'employee_rate' => $request->employee_rate,
                'min_salary_basis' => $request->min_salary_basis,
                'max_salary_basis' => $request->max_salary_basis,
                'effective_year' => $request->effective_year,
                'notes' => $request->notes,
                'is_active' => true,
            ]
        );

        return redirect()->route('bpjs-kes-settings.index')
            ->with('success', 'Pengaturan BPJS Kesehatan berhasil disimpan.');
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'salary' => 'required|numeric|min:0',
        ]);

        $companyId = auth()->user()->company_id;
        $setting = BpjsKesSetting::where('company_id', $companyId)->first();

        if (! $setting) {
            return response()->json(['error' => 'Setting BPJS Kesehatan belum dikonfigurasi'], 422);
        }

        $salary = $request->salary;
        $contribution = $setting->calculateContribution($salary);

        return response()->json([
            'salary' => $salary,
            'contribution' => $contribution,
        ]);
    }
}
