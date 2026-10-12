<?php

namespace App\Http\Controllers;

use App\Models\OvertimeSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeSettingController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');

        $setting = OvertimeSetting::where('company_id', $tenant->id)->first();

        return view('overtime-settings.index', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'weekday_rate_first_hour' => 'required|numeric|min:0.1|max:10',
            'weekday_rate_next_hours' => 'required|numeric|min:0.1|max:10',
            'weekend_rate' => 'required|numeric|min:0.1|max:10',
            'holiday_rate' => 'required|numeric|min:0.1|max:10',
            'max_overtime_hours_per_day' => 'required|integer|min:1|max:12',
            'max_overtime_hours_per_week' => 'required|integer|min:1|max:50',
            'min_overtime_minutes' => 'nullable|integer|min:0|max:60',
            'working_hours_per_month' => 'nullable|integer|min:100|max:200',
            'requires_approval' => 'boolean',
            'auto_calculate_from_attendance' => 'boolean',
        ]);

        $tenant = app('tenant');

        OvertimeSetting::updateOrCreate(
            ['company_id' => $tenant->id],
            [
                'weekday_rate_first_hour' => $validated['weekday_rate_first_hour'],
                'weekday_rate_next_hours' => $validated['weekday_rate_next_hours'],
                'weekend_rate' => $validated['weekend_rate'],
                'holiday_rate' => $validated['holiday_rate'],
                'max_overtime_hours_per_day' => $validated['max_overtime_hours_per_day'],
                'max_overtime_hours_per_week' => $validated['max_overtime_hours_per_week'],
                'min_overtime_minutes' => $validated['min_overtime_minutes'] ?? 30,
                'working_hours_per_month' => $validated['working_hours_per_month'] ?? 173,
                'requires_approval' => $request->boolean('requires_approval'),
                'auto_calculate_from_attendance' => $request->boolean('auto_calculate_from_attendance'),
            ]
        );

        return redirect()->route('overtime-settings.index')
            ->with('success', 'Pengaturan lembur berhasil disimpan.');
    }
}
