@extends('layouts.admin')

@section('title', 'Pengaturan Lembur')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pengaturan Lembur</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan Lembur</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi tarif dan aturan perhitungan lembur.</p>
        </div>
        <a href="{{ route('overtime-requests.index') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Kelola Lembur
        </a>
    </div>
@endsection

@section('content')
    <div class="card max-w-3xl">
        <div class="card-header">
            <h3 class="card-title">Konfigurasi Lembur</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('overtime-settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Weekday Rates --}}
                    <div class="md:col-span-2">
                        <h4 class="text-sm font-semibold text-secondary-700 mb-3 pb-2 border-b">Tarif Hari Kerja (Weekday)</h4>
                    </div>

                    <div>
                        <label for="weekday_rate_first_hour" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tarif Jam Pertama <span class="text-danger-500">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="weekday_rate_first_hour" id="weekday_rate_first_hour"
                                   value="{{ old('weekday_rate_first_hour', $setting?->weekday_rate_first_hour ?? 1.5) }}"
                                   step="0.1" min="0.1" max="10"
                                   class="input w-full @error('weekday_rate_first_hour') border-danger-500 @enderror"
                                   style="padding-right: 40px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">x</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Sesuai UU: 1.5x upah per jam</p>
                        @error('weekday_rate_first_hour')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="weekday_rate_next_hours" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tarif Jam Berikutnya <span class="text-danger-500">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="weekday_rate_next_hours" id="weekday_rate_next_hours"
                                   value="{{ old('weekday_rate_next_hours', $setting?->weekday_rate_next_hours ?? 2.0) }}"
                                   step="0.1" min="0.1" max="10"
                                   class="input w-full @error('weekday_rate_next_hours') border-danger-500 @enderror"
                                   style="padding-right: 40px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">x</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Sesuai UU: 2x upah per jam</p>
                        @error('weekday_rate_next_hours')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Weekend & Holiday Rates --}}
                    <div class="md:col-span-2 mt-4">
                        <h4 class="text-sm font-semibold text-secondary-700 mb-3 pb-2 border-b">Tarif Akhir Pekan & Hari Libur</h4>
                    </div>

                    <div>
                        <label for="weekend_rate" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tarif Akhir Pekan <span class="text-danger-500">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="weekend_rate" id="weekend_rate"
                                   value="{{ old('weekend_rate', $setting?->weekend_rate ?? 2.0) }}"
                                   step="0.1" min="0.1" max="10"
                                   class="input w-full @error('weekend_rate') border-danger-500 @enderror"
                                   style="padding-right: 40px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">x</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Tarif untuk lembur di hari Sabtu/Minggu</p>
                        @error('weekend_rate')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="holiday_rate" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tarif Hari Libur <span class="text-danger-500">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="holiday_rate" id="holiday_rate"
                                   value="{{ old('holiday_rate', $setting?->holiday_rate ?? 3.0) }}"
                                   step="0.1" min="0.1" max="10"
                                   class="input w-full @error('holiday_rate') border-danger-500 @enderror"
                                   style="padding-right: 40px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">x</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Tarif untuk lembur di hari libur nasional</p>
                        @error('holiday_rate')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Limits --}}
                    <div class="md:col-span-2 mt-4">
                        <h4 class="text-sm font-semibold text-secondary-700 mb-3 pb-2 border-b">Batasan Lembur</h4>
                    </div>

                    <div>
                        <label for="max_overtime_hours_per_day" class="block text-sm font-medium text-secondary-700 mb-1">
                            Maks. Jam Lembur/Hari <span class="text-danger-500">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="max_overtime_hours_per_day" id="max_overtime_hours_per_day"
                                   value="{{ old('max_overtime_hours_per_day', $setting?->max_overtime_hours_per_day ?? 4) }}"
                                   min="1" max="12"
                                   class="input w-full @error('max_overtime_hours_per_day') border-danger-500 @enderror"
                                   style="padding-right: 50px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">jam</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Sesuai UU: maksimal 4 jam/hari</p>
                        @error('max_overtime_hours_per_day')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_overtime_hours_per_week" class="block text-sm font-medium text-secondary-700 mb-1">
                            Maks. Jam Lembur/Minggu <span class="text-danger-500">*</span>
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="max_overtime_hours_per_week" id="max_overtime_hours_per_week"
                                   value="{{ old('max_overtime_hours_per_week', $setting?->max_overtime_hours_per_week ?? 14) }}"
                                   min="1" max="50"
                                   class="input w-full @error('max_overtime_hours_per_week') border-danger-500 @enderror"
                                   style="padding-right: 50px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">jam</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Sesuai UU: maksimal 14 jam/minggu</p>
                        @error('max_overtime_hours_per_week')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="min_overtime_minutes" class="block text-sm font-medium text-secondary-700 mb-1">
                            Min. Durasi Lembur
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="min_overtime_minutes" id="min_overtime_minutes"
                                   value="{{ old('min_overtime_minutes', $setting?->min_overtime_minutes ?? 30) }}"
                                   min="0" max="60"
                                   class="input w-full @error('min_overtime_minutes') border-danger-500 @enderror"
                                   style="padding-right: 60px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">menit</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Durasi minimum agar dihitung sebagai lembur</p>
                        @error('min_overtime_minutes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="working_hours_per_month" class="block text-sm font-medium text-secondary-700 mb-1">
                            Jam Kerja/Bulan
                        </label>
                        <div style="position: relative;">
                            <input type="number" name="working_hours_per_month" id="working_hours_per_month"
                                   value="{{ old('working_hours_per_month', $setting?->working_hours_per_month ?? 173) }}"
                                   min="100" max="200"
                                   class="input w-full @error('working_hours_per_month') border-danger-500 @enderror"
                                   style="padding-right: 50px;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #64748b; font-size: 14px;">jam</span>
                        </div>
                        <p class="mt-1 text-sm text-secondary-500">Untuk perhitungan upah per jam (default: 173)</p>
                        @error('working_hours_per_month')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Options --}}
                    <div class="md:col-span-2 mt-4">
                        <h4 class="text-sm font-semibold text-secondary-700 mb-3 pb-2 border-b">Opsi</h4>
                    </div>

                    <div class="md:col-span-2 space-y-3">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="requires_approval" value="0">
                            <input type="checkbox" name="requires_approval" value="1"
                                   {{ old('requires_approval', $setting?->requires_approval ?? true) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="text-sm font-medium text-secondary-700">Memerlukan Persetujuan</span>
                                <p class="text-sm text-secondary-500">Pengajuan lembur harus disetujui admin</p>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="hidden" name="auto_calculate_from_attendance" value="0">
                            <input type="checkbox" name="auto_calculate_from_attendance" value="1"
                                   {{ old('auto_calculate_from_attendance', $setting?->auto_calculate_from_attendance ?? false) ? 'checked' : '' }}
                                   class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                            <div>
                                <span class="text-sm font-medium text-secondary-700">Hitung Otomatis dari Absensi</span>
                                <p class="text-sm text-secondary-500">Lembur dihitung otomatis berdasarkan jam kerja di absensi</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
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
