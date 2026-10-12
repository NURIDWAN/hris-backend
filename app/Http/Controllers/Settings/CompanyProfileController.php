<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateCompanyProfileRequest;
use App\Http\Requests\Settings\UpdateCompanySettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyProfileController extends Controller
{
    public function index(): View
    {
        $company = auth()->user()->company;

        return view('settings.company-profile.index', compact('company'));
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $company = auth()->user()->company;
        $validated = $request->validated();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }

            $path = $request->file('logo')->store('company-logos', 'public');
            $validated['logo'] = $path;
        }

        $company->update($validated);

        return redirect()->route('settings.company-profile.index')
            ->with('success', 'Profil perusahaan berhasil diperbarui.');
    }

    public function deleteLogo(): RedirectResponse
    {
        $company = auth()->user()->company;

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
            $company->update(['logo' => null]);
        }

        return redirect()->route('settings.company-profile.index')
            ->with('success', 'Logo perusahaan berhasil dihapus.');
    }

    public function updateSettings(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        $company = auth()->user()->company;
        $validated = $request->validated();

        // Extract timezone to save directly on company
        $timezone = $validated['timezone'] ?? null;
        unset($validated['timezone']);

        // Merge remaining settings
        $settings = $company->settings ?? [];
        $settings = array_merge($settings, $validated);

        $updateData = ['settings' => $settings];
        if ($timezone) {
            $updateData['timezone'] = $timezone;
        }

        $company->update($updateData);

        return redirect()->route('settings.company-profile.index')
            ->with('success', 'Pengaturan perusahaan berhasil diperbarui.');
    }
}
