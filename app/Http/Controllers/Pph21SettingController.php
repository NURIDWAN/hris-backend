<?php

namespace App\Http\Controllers;

use App\Models\Pph21Rate;
use App\Models\Pph21Setting;
use App\Models\Pph21TerRate;
use App\Models\PtkpSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class Pph21SettingController extends Controller
{
    public function index(): View
    {
        $companyId = auth()->user()->company_id;
        $currentYear = date('Y');

        $setting = Pph21Setting::firstOrCreate(
            ['company_id' => $companyId],
            [
                'is_gross_up' => false,
                'use_ter' => true,
                'npwp_discount_rate' => 20,
                'effective_year' => $currentYear,
            ]
        );

        $ptkpSettings = PtkpSetting::where('company_id', $companyId)
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->orderBy('status_code')
            ->get();

        $pph21Rates = Pph21Rate::where('company_id', $companyId)
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->orderBy('min_amount')
            ->get();

        $terRates = Pph21TerRate::where('company_id', $companyId)
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('min_income')
            ->get()
            ->groupBy('category');

        return view('pph21-settings.index', compact('setting', 'ptkpSettings', 'pph21Rates', 'terRates', 'currentYear'));
    }

    public function updateSetting(Request $request)
    {
        $request->validate([
            'is_gross_up' => 'boolean',
            'use_ter' => 'boolean',
            'npwp_discount_rate' => 'required|numeric|min:0|max:100',
            'effective_year' => 'required|integer|min:2020|max:2030',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = auth()->user()->company_id;

        $setting = Pph21Setting::updateOrCreate(
            ['company_id' => $companyId],
            [
                'is_gross_up' => $request->boolean('is_gross_up'),
                'use_ter' => $request->boolean('use_ter'),
                'npwp_discount_rate' => $request->npwp_discount_rate,
                'effective_year' => $request->effective_year,
                'notes' => $request->notes,
            ]
        );

        return redirect()->route('pph21-settings.index')
            ->with('success', 'Pengaturan PPh 21 berhasil disimpan.');
    }

    public function initializePtkp(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $companyId = auth()->user()->company_id;
        $year = $request->year;

        // Check if already exists
        $exists = PtkpSetting::where('company_id', $companyId)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            return redirect()->route('pph21-settings.index')
                ->with('error', 'Data PTKP untuk tahun '.$year.' sudah ada.');
        }

        // Insert default PTKP values
        $defaults = PtkpSetting::getDefaultPtkp2024();

        DB::transaction(function () use ($companyId, $year, $defaults) {
            foreach ($defaults as $ptkp) {
                PtkpSetting::create([
                    'company_id' => $companyId,
                    'status_code' => $ptkp['status_code'],
                    'description' => $ptkp['description'],
                    'annual_amount' => $ptkp['annual_amount'],
                    'year' => $year,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('pph21-settings.index')
            ->with('success', 'Data PTKP tahun '.$year.' berhasil diinisialisasi.');
    }

    public function initializeRates(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $companyId = auth()->user()->company_id;
        $year = $request->year;

        // Check if already exists
        $exists = Pph21Rate::where('company_id', $companyId)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            return redirect()->route('pph21-settings.index')
                ->with('error', 'Data tarif PPh 21 untuk tahun '.$year.' sudah ada.');
        }

        // Insert default rates
        $defaults = Pph21Rate::getDefaultRates2024();

        DB::transaction(function () use ($companyId, $year, $defaults) {
            foreach ($defaults as $rate) {
                Pph21Rate::create([
                    'company_id' => $companyId,
                    'min_amount' => $rate['min_amount'],
                    'max_amount' => $rate['max_amount'],
                    'rate' => $rate['rate'],
                    'year' => $year,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('pph21-settings.index')
            ->with('success', 'Data tarif PPh 21 tahun '.$year.' berhasil diinisialisasi.');
    }

    public function updatePtkp(Request $request, PtkpSetting $ptkpSetting)
    {
        $this->authorizeCompany($ptkpSetting);

        $request->validate([
            'annual_amount' => 'required|numeric|min:0',
        ]);

        $ptkpSetting->update([
            'annual_amount' => $request->annual_amount,
        ]);

        return redirect()->route('pph21-settings.index')
            ->with('success', 'PTKP '.$ptkpSetting->status_code.' berhasil diperbarui.');
    }

    public function updateRate(Request $request, Pph21Rate $pph21Rate)
    {
        $this->authorizeCompany($pph21Rate);

        $request->validate([
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $pph21Rate->update([
            'rate' => $request->rate,
        ]);

        return redirect()->route('pph21-settings.index')
            ->with('success', 'Tarif PPh 21 berhasil diperbarui.');
    }

    public function initializeTerRates(Request $request): RedirectResponse
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
        ]);

        $companyId = auth()->user()->company_id;
        $year = $request->year;

        $exists = Pph21TerRate::where('company_id', $companyId)
            ->where('year', $year)
            ->exists();

        if ($exists) {
            return redirect()->route('pph21-settings.index')
                ->with('error', 'Data TER untuk tahun '.$year.' sudah ada.');
        }

        $defaults = Pph21TerRate::getDefaultTerRates2024();

        DB::transaction(function () use ($companyId, $year, $defaults) {
            foreach ($defaults as $rate) {
                Pph21TerRate::create([
                    'company_id' => $companyId,
                    'category' => $rate['category'],
                    'ptkp_status' => $rate['ptkp_status'],
                    'min_income' => $rate['min_income'],
                    'max_income' => $rate['max_income'],
                    'rate' => $rate['rate'],
                    'year' => $year,
                    'is_active' => true,
                ]);
            }
        });

        return redirect()->route('pph21-settings.index')
            ->with('success', 'Data TER tahun '.$year.' berhasil diinisialisasi.');
    }

    public function updateTerRate(Request $request, Pph21TerRate $pph21TerRate): RedirectResponse
    {
        $this->authorizeCompany($pph21TerRate);

        $request->validate([
            'rate' => 'required|numeric|min:0|max:100',
        ]);

        $pph21TerRate->update([
            'rate' => $request->rate,
        ]);

        return redirect()->route('pph21-settings.index')
            ->with('success', 'Tarif TER berhasil diperbarui.');
    }

    private function authorizeCompany($model): void
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
