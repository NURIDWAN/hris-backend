<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceSettingController extends AdminBaseController
{
    public function show(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        return $this->successResponse([
            'enable_face_recognition' => (bool) $company->enable_face_recognition,
            'face_match_threshold' => (float) ($company->face_match_threshold ?? 0.8),
            'enable_gps_validation' => (bool) $company->enable_gps_validation,
            'office_radius' => (int) ($company->office_radius ?? 100),
        ], 'Pengaturan absensi berhasil diambil');
    }

    public function update(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $validated = $request->validate([
            'enable_face_recognition' => ['sometimes', 'boolean'],
            'face_match_threshold' => ['sometimes', 'numeric', 'between:0.1,1.0'],
            'enable_gps_validation' => ['sometimes', 'boolean'],
            'office_radius' => ['sometimes', 'integer', 'min:10', 'max:10000'],
        ]);

        $company->update($validated);

        return $this->successResponse([
            'enable_face_recognition' => (bool) $company->enable_face_recognition,
            'face_match_threshold' => (float) $company->face_match_threshold,
            'enable_gps_validation' => (bool) $company->enable_gps_validation,
            'office_radius' => (int) $company->office_radius,
        ], 'Pengaturan absensi berhasil diperbarui');
    }
}
