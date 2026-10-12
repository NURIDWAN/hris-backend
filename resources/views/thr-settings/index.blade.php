@extends('layouts.admin')

@section('title', 'Pengaturan THR')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pajak & BPJS</span>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Pengaturan THR</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan THR</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi perhitungan Tunjangan Hari Raya.</p>
        </div>
        <a href="{{ route('thr.index') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Kelola THR
        </a>
    </div>
@endsection

@section('content')
    <div class="card max-w-2xl">
        <div class="card-header">
            <h3 class="card-title">Konfigurasi THR</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('thr-settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Calculation Method --}}
                <div class="mb-4">
                    <label for="calculation_method" class="block text-sm font-medium text-secondary-700 mb-1">
                        Metode Perhitungan <span class="text-danger-500">*</span>
                    </label>
                    <select name="calculation_method" id="calculation_method"
                            class="input w-full @error('calculation_method') border-danger-500 @enderror">
                        @foreach($calculationMethods as $value => $label)
                            <option value="{{ $value }}" {{ old('calculation_method', $setting?->calculation_method ?? 'one_month_salary') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-secondary-500">1 Bulan Gaji = THR penuh, Prorata = proporsional berdasarkan masa kerja</p>
                    @error('calculation_method')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Min Service Months --}}
                <div class="mb-4">
                    <label for="min_service_months" class="block text-sm font-medium text-secondary-700 mb-1">
                        Minimal Masa Kerja (Bulan) <span class="text-danger-500">*</span>
                    </label>
                    <input type="number" name="min_service_months" id="min_service_months"
                           value="{{ old('min_service_months', $setting?->min_service_months ?? 1) }}"
                           min="0" max="12"
                           class="input w-full @error('min_service_months') border-danger-500 @enderror">
                    <p class="mt-1 text-sm text-secondary-500">Karyawan harus bekerja minimal sekian bulan untuk mendapat THR</p>
                    @error('min_service_months')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Prorata Formula --}}
                <div class="mb-4">
                    <label for="prorata_formula" class="block text-sm font-medium text-secondary-700 mb-1">
                        Formula Prorata <span class="text-danger-500">*</span>
                    </label>
                    <select name="prorata_formula" id="prorata_formula"
                            class="input w-full @error('prorata_formula') border-danger-500 @enderror">
                        @foreach($prorataFormulas as $value => $label)
                            <option value="{{ $value }}" {{ old('prorata_formula', $setting?->prorata_formula ?? 'months_worked_per_12') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-secondary-500">Formula: (Bulan Kerja / 12) x Gaji</p>
                    @error('prorata_formula')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Include Allowances --}}
                <div class="mb-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="include_allowances" value="0">
                        <input type="checkbox" name="include_allowances" value="1"
                               {{ old('include_allowances', $setting?->include_allowances ?? true) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-secondary-300 text-primary-600 focus:ring-primary-500">
                        <div>
                            <span class="text-sm font-medium text-secondary-700">Sertakan Tunjangan</span>
                            <p class="text-sm text-secondary-500">THR dihitung dari gaji pokok + tunjangan tetap</p>
                        </div>
                    </label>
                </div>

                {{-- Religious Holiday --}}
                <div class="mb-4">
                    <label for="religious_holiday" class="block text-sm font-medium text-secondary-700 mb-1">
                        Hari Raya Utama <span class="text-danger-500">*</span>
                    </label>
                    <select name="religious_holiday" id="religious_holiday"
                            class="input w-full @error('religious_holiday') border-danger-500 @enderror">
                        @foreach($religiousHolidays as $value => $label)
                            <option value="{{ $value }}" {{ old('religious_holiday', $setting?->religious_holiday ?? 'idul_fitri') == $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-secondary-500">Hari raya utama untuk pembayaran THR</p>
                    @error('religious_holiday')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Payment Days Before --}}
                <div class="mb-6">
                    <label for="payment_days_before" class="block text-sm font-medium text-secondary-700 mb-1">
                        Pembayaran (Hari Sebelum) <span class="text-danger-500">*</span>
                    </label>
                    <input type="number" name="payment_days_before" id="payment_days_before"
                           value="{{ old('payment_days_before', $setting?->payment_days_before ?? 7) }}"
                           min="1" max="30"
                           class="input w-full @error('payment_days_before') border-danger-500 @enderror">
                    <p class="mt-1 text-sm text-secondary-500">THR harus dibayarkan minimal H-7 sebelum hari raya</p>
                    @error('payment_days_before')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <div class="flex justify-end">
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

    {{-- Info Card --}}
    <div class="card max-w-2xl mt-6">
        <div class="card-header">
            <h3 class="card-title">Informasi THR</h3>
        </div>
        <div class="card-body">
            <div class="prose prose-sm text-secondary-600">
                <p>Berdasarkan Peraturan Pemerintah No. 36 Tahun 2021:</p>
                <ul class="list-disc list-inside space-y-1 mt-2">
                    <li>THR wajib dibayarkan kepada pekerja yang telah bekerja minimal 1 bulan</li>
                    <li>THR dibayarkan paling lambat 7 hari sebelum hari raya</li>
                    <li>Pekerja dengan masa kerja 12 bulan atau lebih berhak atas 1 bulan gaji</li>
                    <li>Pekerja dengan masa kerja kurang dari 12 bulan mendapat THR prorata</li>
                </ul>
            </div>
        </div>
    </div>
@endsection
