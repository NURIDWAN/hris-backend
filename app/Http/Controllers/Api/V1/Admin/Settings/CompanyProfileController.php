<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyProfileController extends AdminBaseController
{
    public function show(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        return $this->successResponse($this->formatCompanyData($company), 'Profil perusahaan berhasil diambil');
    }

    public function update(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'npwp' => ['nullable', 'string', 'max:50'],
            'industry' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'settings' => ['nullable', 'array'],
        ]);

        $settings = $company->settings ?? [];
        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }

        foreach (['city', 'province', 'postal_code', 'industry'] as $field) {
            if ($request->has($field)) {
                $settings[$field] = $request->input($field);
            }
        }

        if (isset($validated['settings']) && is_array($validated['settings'])) {
            $settings = array_merge($settings, $validated['settings']);
        }

        $updateData = collect($validated)->only([
            'name', 'email', 'phone', 'website', 'address', 'timezone',
        ])->all();

        if ($request->has('tax_number')) {
            $updateData['npwp'] = $request->input('tax_number');
        } elseif ($request->has('npwp')) {
            $updateData['npwp'] = $request->input('npwp');
        }

        $updateData['settings'] = $settings;

        $company->update($updateData);

        return $this->successResponse($this->formatCompanyData($company->fresh()), 'Profil perusahaan berhasil diperbarui');
    }

    private function formatCompanyData($company): array
    {
        $data = $company->toArray();
        $settings = $company->settings ?? [];
        if (is_string($settings)) {
            $settings = json_decode($settings, true) ?? [];
        }

        $data['city'] = $settings['city'] ?? null;
        $data['province'] = $settings['province'] ?? null;
        $data['postal_code'] = $settings['postal_code'] ?? null;
        $data['industry'] = $settings['industry'] ?? null;
        $data['tax_number'] = $company->npwp ?? ($settings['tax_number'] ?? null);

        return $data;
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
        ]);

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
        }

        $path = $request->file('logo')->store('company-logos', 'public');
        $company->update(['logo' => $path]);

        return $this->successResponse([
            'logo' => $path,
            'logo_url' => Storage::disk('public')->url($path),
        ], 'Logo perusahaan berhasil diupload');
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        if ($company->logo) {
            Storage::disk('public')->delete($company->logo);
            $company->update(['logo' => null]);
        }

        return $this->successResponse(null, 'Logo perusahaan berhasil dihapus');
    }
}
