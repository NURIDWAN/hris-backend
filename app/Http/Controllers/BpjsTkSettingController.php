<?php

namespace App\Http\Controllers;

use App\Models\BpjsTkSetting;
use App\Models\JkkRiskRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BpjsTkSettingController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id;
        $currentYear = date('Y');

        $setting = BpjsTkSetting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'jht_company_rate' => 3.70,
                'jht_employee_rate' => 2.00,
                'jht_enabled' => true,
                'jkk_rate' => 0.24,
                'jkk_risk_level' => 'very_low',
                'jkk_enabled' => true,
                'jkm_rate' => 0.30,
                'jkm_enabled' => true,
                'jp_company_rate' => 2.00,
                'jp_employee_rate' => 1.00,
                'jp_max_salary' => 10042300,
                'jp_enabled' => true,
                'effective_year' => $currentYear,
                'is_active' => true,
            ]
        );

        $jkkRiskRates = JkkRiskRate::where('company_id', $companyId)
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->orderBy('rate')
            ->get();

        $jkkRiskOptions = BpjsTkSetting::getJkkRiskOptions();

        return view('bpjs-tk-settings.index', compact('setting', 'jkkRiskRates', 'jkkRiskOptions', 'currentYear'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'jht_company_rate' => 'required|numeric|min:0|max:100',
            'jht_employee_rate' => 'required|numeric|min:0|max:100',
            'jht_enabled' => 'boolean',
            'jkk_risk_level' => 'required|in:very_low,low,medium,high,very_high',
            'jkk_enabled' => 'boolean',
            'jkm_rate' => 'required|numeric|min:0|max:100',
            'jkm_enabled' => 'boolean',
            'jp_company_rate' => 'required|numeric|min:0|max:100',
            'jp_employee_rate' => 'required|numeric|min:0|max:100',
            'jp_max_salary' => 'required|numeric|min:0',
            'jp_enabled' => 'boolean',
            'effective_year' => 'required|integer|min:2020|max:2030',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = auth()->user()->company_id;

        // Get JKK rate based on risk level
        $jkkRiskOptions = BpjsTkSetting::getJkkRiskOptions();
        $jkkRate = $jkkRiskOptions[$request->jkk_risk_level]['rate'];

        BpjsTkSetting::updateOrCreate(
            ['company_id' => $companyId],
            [
                'jht_company_rate' => $request->jht_company_rate,
                'jht_employee_rate' => $request->jht_employee_rate,
                'jht_enabled' => $request->boolean('jht_enabled'),
                'jkk_rate' => $jkkRate,
                'jkk_risk_level' => $request->jkk_risk_level,
                'jkk_enabled' => $request->boolean('jkk_enabled'),
                'jkm_rate' => $request->jkm_rate,
                'jkm_enabled' => $request->boolean('jkm_enabled'),
                'jp_company_rate' => $request->jp_company_rate,
                'jp_employee_rate' => $request->jp_employee_rate,
                'jp_max_salary' => $request->jp_max_salary,
                'jp_enabled' => $request->boolean('jp_enabled'),
                'effective_year' => $request->effective_year,
                'notes' => $request->notes,
                'is_active' => true,
            ]
        );

        return redirect()->route('bpjs-tk-settings.index')
            ->with('success', 'Pengaturan BPJS Ketenagakerjaan berhasil disimpan.');
    }

    public function initializeJkkRates(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $companyId = auth()->user()->company_id;
        $year = $request->year;

        // Check if already exists
        $exists = JkkRiskRate::where('company_id', $companyId)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            return redirect()->route('bpjs-tk-settings.index')
                ->with('error', 'Data tarif JKK untuk tahun '.$year.' sudah ada.');
        }

        // Insert default JKK rates
        $defaults = JkkRiskRate::getDefaultRates();

        DB::transaction(function () use ($companyId, $year, $defaults) {
            foreach ($defaults as $rate) {
                JkkRiskRate::create([
                    'company_id' => $companyId,
                    'risk_level' => $rate['risk_level'],
                    'description' => $rate['description'],
                    'rate' => $rate['rate'],
                    'year' => $year,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('bpjs-tk-settings.index')
            ->with('success', 'Data tarif JKK tahun '.$year.' berhasil diinisialisasi.');
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'salary' => 'required|numeric|min:0',
        ]);

        $companyId = auth()->user()->company_id;
        $setting = BpjsTkSetting::where('company_id', $companyId)->first();

        if (! $setting) {
            return response()->json(['error' => 'Setting BPJS TK belum dikonfigurasi'], 422);
        }

        $salary = $request->salary;
        $employeeContribution = $setting->calculateEmployeeContribution($salary);
        $companyContribution = $setting->calculateCompanyContribution($salary);

        return response()->json([
            'salary' => $salary,
            'employee' => $employeeContribution,
            'company' => $companyContribution,
            'total' => [
                'employee' => $employeeContribution['total'],
                'company' => $companyContribution['total'],
                'grand_total' => $employeeContribution['total'] + $companyContribution['total'],
            ],
        ]);
    }
}
