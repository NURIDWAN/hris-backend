<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateThrSettingRequest;
use App\Models\ThrSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ThrSettingController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');

        $setting = ThrSetting::where('company_id', $tenant->id)->first();

        return view('thr-settings.index', [
            'setting' => $setting,
            'calculationMethods' => ThrSetting::CALCULATION_METHODS,
            'religiousHolidays' => ThrSetting::RELIGIOUS_HOLIDAYS,
            'prorataFormulas' => ThrSetting::PRORATA_FORMULAS,
        ]);
    }

    public function update(UpdateThrSettingRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        ThrSetting::updateOrCreate(
            ['company_id' => $tenant->id],
            $request->validated()
        );

        return redirect()->route('thr-settings.index')
            ->with('success', 'Pengaturan THR berhasil disimpan.');
    }
}
