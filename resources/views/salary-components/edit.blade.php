@extends('layouts.admin')

@section('title', 'Edit Komponen Gaji')

@section('breadcrumb')
    <a href="{{ route('salary-components.index') }}" class="text-slate-500 hover:text-primary-600">Komponen Gaji</a>
    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="text-slate-700 font-medium">Edit Komponen</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Edit Komponen Gaji</h1>
            <p class="text-secondary-500 mt-1">Perbarui informasi komponen gaji.</p>
        </div>
        <a href="{{ route('salary-components.index') }}" class="btn btn-ghost">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Kembali
        </a>
    </div>
@endsection

@section('content')
    <div class="max-w-3xl">
        <form action="{{ route('salary-components.update', $salaryComponent) }}" method="POST"
              x-data="{
                  calculationType: '{{ old('calculation_type', $salaryComponent->calculation_type) }}',
                  componentType: '{{ old('type', $salaryComponent->type) }}',
                  isAttendanceBased: {{ old('is_attendance_based', $salaryComponent->is_attendance_based) ? 'true' : 'false' }},
                  attendanceCalculation: '{{ old('attendance_calculation', $salaryComponent->attendance_calculation ?? 'daily') }}'
              }">
            @csrf
            @method('PUT')

            {{-- Basic Info --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Informasi Dasar</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Name --}}
                        <div>
                            <label for="name" class="block text-sm font-medium text-secondary-700 mb-1">
                                Nama Komponen <span class="text-danger-500">*</span>
                            </label>
                            <input type="text" name="name" id="name" value="{{ old('name', $salaryComponent->name) }}"
                                   class="input w-full @error('name') border-danger-500 @enderror"
                                   placeholder="Contoh: Tunjangan Transport" required>
                            @error('name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Code --}}
                        <div>
                            <label for="code" class="block text-sm font-medium text-secondary-700 mb-1">
                                Kode
                            </label>
                            <input type="text" name="code" id="code" value="{{ old('code', $salaryComponent->code) }}"
                                   class="input w-full @error('code') border-danger-500 @enderror"
                                   placeholder="Contoh: TT">
                            @error('code')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div>
                        <label for="description" class="block text-sm font-medium text-secondary-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea name="description" id="description" rows="2"
                                  class="input w-full @error('description') border-danger-500 @enderror"
                                  placeholder="Deskripsi singkat tentang komponen ini...">{{ old('description', $salaryComponent->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Type --}}
                        <div>
                            <label for="type" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tipe Komponen <span class="text-danger-500">*</span>
                            </label>
                            <select name="type" id="type" x-model="componentType"
                                    class="input w-full @error('type') border-danger-500 @enderror" required>
                                <option value="earning" {{ old('type', $salaryComponent->type) === 'earning' ? 'selected' : '' }}>Pendapatan (Tambahan)</option>
                                <option value="deduction" {{ old('type', $salaryComponent->type) === 'deduction' ? 'selected' : '' }}>Potongan</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Category --}}
                        <div>
                            <label for="category" class="block text-sm font-medium text-secondary-700 mb-1">
                                Kategori <span class="text-danger-500">*</span>
                            </label>
                            <select name="category" id="category"
                                    class="input w-full @error('category') border-danger-500 @enderror" required>
                                <template x-if="componentType === 'earning'">
                                    <optgroup label="Kategori Pendapatan">
                                        <option value="fixed" {{ old('category', $salaryComponent->category) === 'fixed' ? 'selected' : '' }}>Tetap</option>
                                        <option value="variable" {{ old('category', $salaryComponent->category) === 'variable' ? 'selected' : '' }}>Variabel</option>
                                        <option value="benefit" {{ old('category', $salaryComponent->category) === 'benefit' ? 'selected' : '' }}>Tunjangan</option>
                                    </optgroup>
                                </template>
                                <template x-if="componentType === 'deduction'">
                                    <optgroup label="Kategori Potongan">
                                        <option value="tax" {{ old('category', $salaryComponent->category) === 'tax' ? 'selected' : '' }}>Pajak</option>
                                        <option value="insurance" {{ old('category', $salaryComponent->category) === 'insurance' ? 'selected' : '' }}>Asuransi</option>
                                        <option value="loan" {{ old('category', $salaryComponent->category) === 'loan' ? 'selected' : '' }}>Pinjaman</option>
                                        <option value="other" {{ old('category', $salaryComponent->category) === 'other' ? 'selected' : '' }}>Lainnya</option>
                                    </optgroup>
                                </template>
                            </select>
                            @error('category')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calculation --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Perhitungan</h3>
                </div>
                <div class="card-body space-y-4">
                    {{-- Calculation Type --}}
                    <div>
                        <label class="block text-sm font-medium text-secondary-700 mb-3">
                            Tipe Perhitungan <span class="text-danger-500">*</span>
                        </label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="calculation_type" value="fixed" x-model="calculationType"
                                       class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                                       {{ old('calculation_type', $salaryComponent->calculation_type) === 'fixed' ? 'checked' : '' }}>
                                <span class="text-sm text-secondary-700">Nominal Tetap</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="calculation_type" value="percentage" x-model="calculationType"
                                       class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500"
                                       {{ old('calculation_type', $salaryComponent->calculation_type) === 'percentage' ? 'checked' : '' }}>
                                <span class="text-sm text-secondary-700">Persentase</span>
                            </label>
                        </div>
                        @error('calculation_type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Fixed Amount --}}
                    <div x-show="calculationType === 'fixed'" x-cloak x-data="currencyInput({{ old('default_amount', $salaryComponent->default_amount ?? 0) }})">
                        <label for="default_amount_display" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nominal <span class="text-danger-500">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="default_amount_display" x-model="display" @input="updateValue($event)"
                                   class="input @error('default_amount') border-danger-500 @enderror"
                                   placeholder="0" inputmode="numeric">
                            <input type="hidden" name="default_amount" :value="value">
                        </div>
                        @error('default_amount')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Percentage --}}
                    <div x-show="calculationType === 'percentage'" x-cloak class="space-y-4">
                        <div>
                            <label for="percentage" class="block text-sm font-medium text-secondary-700 mb-1">
                                Persentase <span class="text-danger-500">*</span>
                            </label>
                            <div class="relative w-40">
                                <input type="number" name="percentage" id="percentage" value="{{ old('percentage', $salaryComponent->percentage) }}"
                                       class="input w-full pr-8 @error('percentage') border-danger-500 @enderror"
                                       placeholder="0" min="0" max="100" step="0.01">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-500">%</span>
                            </div>
                            @error('percentage')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="percentage_base" class="block text-sm font-medium text-secondary-700 mb-1">
                                Basis Persentase
                            </label>
                            <select name="percentage_base" id="percentage_base"
                                    class="input w-full md:w-1/2 @error('percentage_base') border-danger-500 @enderror">
                                <option value="basic_salary" {{ old('percentage_base', $salaryComponent->percentage_base ?? 'basic_salary') === 'basic_salary' ? 'selected' : '' }}>Gaji Pokok</option>
                                <option value="gross_salary" {{ old('percentage_base', $salaryComponent->percentage_base) === 'gross_salary' ? 'selected' : '' }}>Gaji Kotor</option>
                            </select>
                            @error('percentage_base')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">Nilai yang menjadi basis perhitungan persentase.</p>
                        </div>
                    </div>

                    {{-- Sort Order --}}
                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-secondary-700 mb-1">
                            Urutan Tampilan
                        </label>
                        <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $salaryComponent->sort_order) }}"
                               class="input w-32 @error('sort_order') border-danger-500 @enderror"
                               min="0">
                        @error('sort_order')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-secondary-500">Urutan tampilan di slip gaji (kecil lebih dulu).</p>
                    </div>
                </div>
            </div>

            {{-- Attendance-Based Settings --}}
            <div class="card mb-6" x-show="componentType === 'earning'">
                <div class="card-header">
                    <h3 class="card-title">Berbasis Kehadiran</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_attendance_based" value="0">
                        <input type="checkbox" name="is_attendance_based" id="is_attendance_based" value="1"
                               x-model="isAttendanceBased"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <div>
                            <label for="is_attendance_based" class="text-sm font-medium text-secondary-700">
                                Hitung Berdasarkan Kehadiran
                            </label>
                            <p class="text-sm text-secondary-500">Komponen ini dihitung berdasarkan jumlah hari hadir (misal: uang makan, transport).</p>
                        </div>
                    </div>

                    <div x-show="isAttendanceBased" x-cloak class="space-y-4 pt-4 border-t border-secondary-200">
                        {{-- Attendance Calculation Type --}}
                        <div>
                            <label class="block text-sm font-medium text-secondary-700 mb-2">
                                Metode Perhitungan
                            </label>
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="attendance_calculation" value="daily" x-model="attendanceCalculation"
                                           class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500">
                                    <span class="text-sm text-secondary-700">Per Hari Hadir (misal: Rp 50.000/hari)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="attendance_calculation" value="monthly" x-model="attendanceCalculation"
                                           class="w-4 h-4 text-primary-600 border-secondary-300 focus:ring-primary-500">
                                    <span class="text-sm text-secondary-700">Bulanan Potong Proporsional (misal: Rp 500.000/bulan - potong jika tidak masuk)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Daily Rate --}}
                        <div x-show="attendanceCalculation === 'daily'" x-cloak x-data="currencyInput({{ old('daily_rate', $salaryComponent->daily_rate ?? 0) }})">
                            <label for="daily_rate_display" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tarif per Hari <span class="text-danger-500">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="daily_rate_display" x-model="display" @input="updateValue($event)"
                                       class="input @error('daily_rate') border-danger-500 @enderror"
                                       placeholder="50.000" inputmode="numeric">
                                <input type="hidden" name="daily_rate" :value="value">
                            </div>
                            @error('daily_rate')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-secondary-500">Nominal yang diterima per hari kehadiran.</p>
                        </div>

                        {{-- Additional Options --}}
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <input type="hidden" name="deduct_for_late" value="0">
                                <input type="checkbox" name="deduct_for_late" id="deduct_for_late" value="1"
                                       class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                       {{ old('deduct_for_late', $salaryComponent->deduct_for_late) ? 'checked' : '' }}>
                                <label for="deduct_for_late" class="text-sm text-secondary-700">
                                    Potong jika terlambat (hari terlambat tidak dihitung)
                                </label>
                            </div>

                            <div class="flex items-center gap-3">
                                <input type="hidden" name="include_half_days" value="0">
                                <input type="checkbox" name="include_half_days" id="include_half_days" value="1"
                                       class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                                       {{ old('include_half_days', $salaryComponent->include_half_days ?? true) ? 'checked' : '' }}>
                                <label for="include_half_days" class="text-sm text-secondary-700">
                                    Hitung setengah hari sebagai 0.5
                                </label>
                            </div>
                        </div>

                        <div class="p-3 bg-info-50 rounded-lg">
                            <p class="text-sm text-info-700">
                                <strong>Contoh:</strong> Jika tarif Rp 50.000/hari dan karyawan hadir 20 hari, maka tunjangan = 20 × Rp 50.000 = Rp 1.000.000
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Settings --}}
            <div class="card mb-6">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_taxable" value="0">
                        <input type="checkbox" name="is_taxable" id="is_taxable" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_taxable', $salaryComponent->is_taxable) ? 'checked' : '' }}>
                        <div>
                            <label for="is_taxable" class="text-sm font-medium text-secondary-700">
                                Kena Pajak
                            </label>
                            <p class="text-sm text-secondary-500">Komponen ini dihitung untuk dasar pajak penghasilan.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_mandatory" value="0">
                        <input type="checkbox" name="is_mandatory" id="is_mandatory" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_mandatory', $salaryComponent->is_mandatory) ? 'checked' : '' }}>
                        <div>
                            <label for="is_mandatory" class="text-sm font-medium text-secondary-700">
                                Wajib
                            </label>
                            <p class="text-sm text-secondary-500">Komponen ini otomatis diterapkan ke semua karyawan.</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" id="is_active" value="1"
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500"
                               {{ old('is_active', $salaryComponent->is_active) ? 'checked' : '' }}>
                        <div>
                            <label for="is_active" class="text-sm font-medium text-secondary-700">
                                Aktif
                            </label>
                            <p class="text-sm text-secondary-500">Komponen ini dapat digunakan dalam perhitungan gaji.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('salary-components.index') }}" class="btn btn-ghost">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
@endsection
