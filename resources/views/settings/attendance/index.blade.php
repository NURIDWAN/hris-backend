@extends('layouts.admin')

@section('title', 'Pengaturan Absensi')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pengaturan Absensi</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan Absensi</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi Face Recognition dan validasi GPS untuk absensi.</p>
        </div>
        <a href="{{ route('attendances.index') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Kelola Absensi
        </a>
    </div>
@endsection

@section('content')
    <div class="card max-w-3xl">
        <div class="card-header">
            <h3 class="card-title">Konfigurasi Absensi</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('settings.attendance.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="space-y-8">
                    {{-- Face Recognition Section --}}
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-secondary-900">Face Recognition</h4>
                                <p class="text-sm text-secondary-500">Verifikasi wajah karyawan saat absensi</p>
                            </div>
                        </div>

                        <div class="space-y-4 pl-13">
                            {{-- Enable Face Recognition --}}
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="enable_face_recognition" value="0">
                                <input type="checkbox" name="enable_face_recognition" value="1"
                                       {{ old('enable_face_recognition', $company->enable_face_recognition) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                <div>
                                    <span class="text-sm font-medium text-secondary-700">Aktifkan Face Recognition</span>
                                    <p class="text-sm text-secondary-500">Karyawan harus memverifikasi wajah saat clock in/out</p>
                                </div>
                            </label>

                            {{-- Face Match Threshold --}}
                            <div>
                                <label for="face_match_threshold" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Tingkat Kecocokan Wajah <span class="text-danger-500">*</span>
                                </label>
                                <div class="flex items-center gap-4">
                                    <input type="range" name="face_match_threshold" id="face_match_threshold"
                                           value="{{ old('face_match_threshold', $company->face_match_threshold ?? 0.75) }}"
                                           min="0.5" max="1" step="0.05"
                                           class="flex-1 h-2 bg-secondary-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
                                           oninput="document.getElementById('threshold_value').textContent = (this.value * 100).toFixed(0) + '%'">
                                    <span id="threshold_value" class="text-sm font-semibold text-primary-600 w-12 text-right">
                                        {{ number_format(($company->face_match_threshold ?? 0.75) * 100, 0) }}%
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-secondary-500">
                                    Semakin tinggi nilai, semakin ketat verifikasi. Rekomendasi: 70-80%
                                </p>
                                @error('face_match_threshold')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Info Box --}}
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex gap-3">
                                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <div class="text-sm text-blue-700">
                                        <p class="font-medium">Tentang Face Recognition</p>
                                        <p class="mt-1">Karyawan perlu mendaftarkan wajah terlebih dahulu melalui menu <strong>Face Recognition</strong> sebelum dapat menggunakan fitur ini.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="border-secondary-200">

                    {{-- GPS Validation Section --}}
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-lg bg-success-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-success-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-semibold text-secondary-900">Validasi GPS</h4>
                                <p class="text-sm text-secondary-500">Pastikan karyawan berada di lokasi kantor saat absensi</p>
                            </div>
                        </div>

                        <div class="space-y-4 pl-13">
                            {{-- Enable GPS Validation --}}
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="hidden" name="enable_gps_validation" value="0">
                                <input type="checkbox" name="enable_gps_validation" value="1"
                                       {{ old('enable_gps_validation', $company->enable_gps_validation) ? 'checked' : '' }}
                                       class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                                <div>
                                    <span class="text-sm font-medium text-secondary-700">Aktifkan Validasi GPS</span>
                                    <p class="text-sm text-secondary-500">Karyawan harus berada dalam radius lokasi kantor saat absensi</p>
                                </div>
                            </label>

                            {{-- Default Office Radius --}}
                            <div>
                                <label for="office_radius" class="block text-sm font-medium text-secondary-700 mb-1">
                                    Radius Default Kantor <span class="text-danger-500">*</span>
                                </label>
                                <div style="position: relative;">
                                    <input type="number" name="office_radius" id="office_radius"
                                           value="{{ old('office_radius', $company->office_radius ?? 100) }}"
                                           min="10" max="5000"
                                           class="input w-full @error('office_radius') border-danger-500 @enderror"
                                           style="padding-right: 60px;">
                                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">meter</span>
                                </div>
                                <p class="mt-1 text-sm text-secondary-500">
                                    Radius default untuk validasi GPS. Dapat diatur berbeda per lokasi kantor.
                                </p>
                                @error('office_radius')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Info Box --}}
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                                <div class="flex gap-3">
                                    <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    <div class="text-sm text-amber-700">
                                        <p class="font-medium">Pengaturan Per Lokasi Kantor</p>
                                        <p class="mt-1">Setiap lokasi kantor dapat memiliki radius GPS yang berbeda. Atur melalui menu <a href="{{ route('office-locations.index') }}" class="underline font-medium">Lokasi Kantor</a>.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-8 pt-4 border-t">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
