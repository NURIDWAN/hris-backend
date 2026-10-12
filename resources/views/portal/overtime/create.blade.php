@extends('layouts.portal')

@section('title', 'Ajukan Lembur')

@section('content')
    <div class="max-w-2xl mx-auto space-y-6">
        {{-- Header --}}
        <div>
            <a href="{{ route('portal.overtime.index') }}" class="text-primary-600 hover:text-primary-700 inline-flex items-center gap-1 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Riwayat Lembur
            </a>
            <h1 class="text-2xl font-bold text-secondary-900">Ajukan Lembur</h1>
            <p class="text-secondary-500 mt-1">Buat pengajuan lembur baru.</p>
        </div>

        {{-- Form --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Form Pengajuan Lembur</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('portal.overtime.store') }}" method="POST">
                    @csrf

                    {{-- Date --}}
                    <div class="mb-4">
                        <label for="date" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal Lembur <span class="text-danger-500">*</span>
                        </label>
                        <input type="date" name="date" id="date"
                               value="{{ old('date') }}"
                               class="input w-full @error('date') border-danger-500 @enderror" required>
                        @error('date')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Time --}}
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Mulai <span class="text-danger-500">*</span>
                            </label>
                            <input type="time" name="start_time" id="start_time"
                                   value="{{ old('start_time', '18:00') }}"
                                   class="input w-full @error('start_time') border-danger-500 @enderror" required>
                            @error('start_time')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-secondary-700 mb-1">
                                Jam Selesai <span class="text-danger-500">*</span>
                            </label>
                            <input type="time" name="end_time" id="end_time"
                                   value="{{ old('end_time', '21:00') }}"
                                   class="input w-full @error('end_time') border-danger-500 @enderror" required>
                            @error('end_time')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Overtime Type --}}
                    <div class="mb-4">
                        <label for="overtime_type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tipe Lembur <span class="text-danger-500">*</span>
                        </label>
                        <select name="overtime_type" id="overtime_type"
                                class="input w-full @error('overtime_type') border-danger-500 @enderror" required>
                            <option value="weekday" {{ old('overtime_type') == 'weekday' ? 'selected' : '' }}>Hari Kerja (Weekday)</option>
                            <option value="weekend" {{ old('overtime_type') == 'weekend' ? 'selected' : '' }}>Akhir Pekan (Weekend)</option>
                            <option value="holiday" {{ old('overtime_type') == 'holiday' ? 'selected' : '' }}>Hari Libur Nasional</option>
                        </select>
                        @if($setting)
                            <p class="mt-1 text-sm text-secondary-500">
                                Tarif: Weekday {{ $setting->weekday_rate_first_hour }}x (jam 1), {{ $setting->weekday_rate_next_hours }}x (jam berikut) |
                                Weekend {{ $setting->weekend_rate }}x |
                                Libur {{ $setting->holiday_rate }}x
                            </p>
                        @endif
                        @error('overtime_type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Reason --}}
                    <div class="mb-4">
                        <label for="reason" class="block text-sm font-medium text-secondary-700 mb-1">
                            Alasan Lembur <span class="text-danger-500">*</span>
                        </label>
                        <textarea name="reason" id="reason" rows="3"
                                  class="input w-full @error('reason') border-danger-500 @enderror"
                                  placeholder="Jelaskan alasan/pekerjaan yang akan dilakukan" required>{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <a href="{{ route('portal.overtime.index') }}" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajukan Lembur
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Info Card --}}
        <div class="card bg-info-50 border-info-200">
            <div class="card-body">
                <h4 class="font-semibold text-info-800 mb-2">Informasi Lembur</h4>
                <ul class="text-sm text-info-700 space-y-1">
                    <li>- Pengajuan lembur memerlukan persetujuan atasan.</li>
                    <li>- Maksimal lembur per hari: 4 jam (sesuai UU Ketenagakerjaan).</li>
                    <li>- Maksimal lembur per minggu: 14 jam.</li>
                    <li>- Nilai lembur akan dihitung otomatis berdasarkan gaji Anda.</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
