<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceSettingController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        return view('settings.attendance.index', compact('company'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enable_face_recognition' => ['required', 'boolean'],
            'face_match_threshold' => ['required', 'numeric', 'min:0.5', 'max:1'],
            'enable_gps_validation' => ['required', 'boolean'],
            'office_radius' => ['required', 'integer', 'min:10', 'max:5000'],
        ]);

        $company = auth()->user()->company;
        $company->update($validated);

        return redirect()->route('settings.attendance.index')
            ->with('success', 'Pengaturan absensi berhasil diperbarui.');
    }
}
