@extends('layouts.admin')

@section('title', 'Pengaturan Payroll')

@section('breadcrumb')
    <span class="text-slate-700 font-medium">Pengaturan Payroll</span>
@endsection

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-secondary-900">Pengaturan Payroll</h1>
            <p class="text-secondary-500 mt-1">Konfigurasi siklus payroll dan perhitungan gaji.</p>
        </div>
    </div>
@endsection

@section('content')
    {{-- Summary Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
        <div class="stat-card">
            <p class="stat-card-label">Periode Payroll</p>
            <p class="text-lg font-bold text-primary-600">{{ $setting->cutoff_description }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Hari Kerja Default</p>
            <p class="text-lg font-bold text-warning-600">{{ $setting->default_working_days }} hari</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Tanggal Gajian</p>
            <p class="text-lg font-bold text-success-600">Tanggal {{ $setting->payment_day }}</p>
        </div>
        <div class="stat-card">
            <p class="stat-card-label">Potong Absen</p>
            <p class="text-lg font-bold text-secondary-900">{{ $setting->auto_deduct_absent ? 'Ya' : 'Tidak' }}</p>
        </div>
    </div>

    <form action="{{ route('payroll-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {{-- Siklus Payroll --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Siklus Payroll</h3>
                </div>
                <div class="card-body space-y-4">
                    <div>
                        <label for="cycle_type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tipe Siklus <span class="text-danger-500">*</span>
                        </label>
                        <select name="cycle_type" id="cycle_type"
                                class="input w-full @error('cycle_type') border-danger-500 @enderror">
                            <option value="monthly" {{ old('cycle_type', $setting->cycle_type) === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                        </select>
                        @error('cycle_type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="cutoff_day" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tanggal Cutoff <span class="text-danger-500">*</span>
                        </label>
                        <select name="cutoff_day" id="cutoff_day"
                                class="input w-full @error('cutoff_day') border-danger-500 @enderror">
                            <option value="1" {{ old('cutoff_day', $setting->cutoff_day) == 1 ? 'selected' : '' }}>Tanggal 1 (1 - Akhir Bulan)</option>
                            <option value="15" {{ old('cutoff_day', $setting->cutoff_day) == 15 ? 'selected' : '' }}>Tanggal 15 (15 - 14)</option>
                            <option value="20" {{ old('cutoff_day', $setting->cutoff_day) == 20 ? 'selected' : '' }}>Tanggal 20 (20 - 19)</option>
                            <option value="21" {{ old('cutoff_day', $setting->cutoff_day) == 21 ? 'selected' : '' }}>Tanggal 21 (21 - 20)</option>
                            <option value="25" {{ old('cutoff_day', $setting->cutoff_day) == 25 ? 'selected' : '' }}>Tanggal 25 (25 - 24)</option>
                            <option value="26" {{ old('cutoff_day', $setting->cutoff_day) == 26 ? 'selected' : '' }}>Tanggal 26 (26 - 25)</option>
                        </select>
                        @error('cutoff_day')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-secondary-400 mt-1">Contoh: Tanggal 25 berarti periode 25 bulan lalu s/d 24 bulan ini</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="default_working_days" class="block text-sm font-medium text-secondary-700 mb-1">
                                Hari Kerja Default <span class="text-danger-500">*</span>
                            </label>
                            <input type="number" name="default_working_days" id="default_working_days"
                                   value="{{ old('default_working_days', $setting->default_working_days) }}"
                                   min="1" max="31"
                                   class="input w-full @error('default_working_days') border-danger-500 @enderror">
                            @error('default_working_days')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-secondary-400 mt-1">Untuk perhitungan rate harian</p>
                        </div>
                        <div>
                            <label for="payment_day" class="block text-sm font-medium text-secondary-700 mb-1">
                                Tanggal Gajian <span class="text-danger-500">*</span>
                            </label>
                            <input type="number" name="payment_day" id="payment_day"
                                   value="{{ old('payment_day', $setting->payment_day) }}"
                                   min="1" max="28"
                                   class="input w-full @error('payment_day') border-danger-500 @enderror">
                            @error('payment_day')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pengaturan Potongan Absen --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Potongan Kehadiran</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="auto_deduct_absent" id="auto_deduct_absent" value="1"
                               {{ old('auto_deduct_absent', $setting->auto_deduct_absent) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="auto_deduct_absent" class="text-sm font-medium text-secondary-700">
                            Otomatis potong tunjangan untuk hari tidak masuk
                        </label>
                    </div>

                    <div>
                        <label for="absent_deduction_type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tipe Potongan Absen
                        </label>
                        <select name="absent_deduction_type" id="absent_deduction_type"
                                class="input w-full @error('absent_deduction_type') border-danger-500 @enderror">
                            <option value="daily_rate" {{ old('absent_deduction_type', $setting->absent_deduction_type) === 'daily_rate' ? 'selected' : '' }}>Rate Harian (Gaji / Hari Kerja)</option>
                            <option value="fixed" {{ old('absent_deduction_type', $setting->absent_deduction_type) === 'fixed' ? 'selected' : '' }}>Nominal Tetap per Hari</option>
                            <option value="percentage" {{ old('absent_deduction_type', $setting->absent_deduction_type) === 'percentage' ? 'selected' : '' }}>Persentase Gaji per Hari</option>
                        </select>
                        @error('absent_deduction_type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="currencyInput({{ old('absent_deduction_amount', $setting->absent_deduction_amount ?? 0) }})">
                        <label for="absent_deduction_amount_display" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nominal Potongan per Hari
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="absent_deduction_amount_display" x-model="display" @input="updateValue($event)"
                                   class="input w-full rounded-l-none @error('absent_deduction_amount') border-danger-500 @enderror">
                            <input type="hidden" name="absent_deduction_amount" :value="value">
                        </div>
                        @error('absent_deduction_amount')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-secondary-400 mt-1">Untuk tipe "Nominal Tetap". Kosongkan jika menggunakan rate harian.</p>
                    </div>

                    <div class="mt-4 p-3 bg-info-50 rounded-lg">
                        <p class="text-sm text-info-700">
                            <strong>Info:</strong> Potongan akan diterapkan pada komponen tunjangan yang ditandai sebagai
                            "berbasis kehadiran" (contoh: tunjangan makan, transport).
                        </p>
                    </div>
                </div>
            </div>

            {{-- Pengaturan Keterlambatan --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Penalti Keterlambatan</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="auto_deduct_late" id="auto_deduct_late" value="1"
                               {{ old('auto_deduct_late', $setting->auto_deduct_late) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="auto_deduct_late" class="text-sm font-medium text-secondary-700">
                            Aktifkan penalti keterlambatan
                        </label>
                    </div>

                    <div>
                        <label for="late_penalty_threshold" class="block text-sm font-medium text-secondary-700 mb-1">
                            Batas Toleransi Terlambat (kali/bulan)
                        </label>
                        <input type="number" name="late_penalty_threshold" id="late_penalty_threshold"
                               value="{{ old('late_penalty_threshold', $setting->late_penalty_threshold) }}"
                               min="1" max="31"
                               class="input w-full @error('late_penalty_threshold') border-danger-500 @enderror">
                        @error('late_penalty_threshold')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-secondary-400 mt-1">Penalti diterapkan setelah melebihi batas ini</p>
                    </div>

                    <div>
                        <label for="late_penalty_type" class="block text-sm font-medium text-secondary-700 mb-1">
                            Tipe Penalti
                        </label>
                        <select name="late_penalty_type" id="late_penalty_type"
                                class="input w-full @error('late_penalty_type') border-danger-500 @enderror">
                            <option value="fixed" {{ old('late_penalty_type', $setting->late_penalty_type) === 'fixed' ? 'selected' : '' }}>Nominal Tetap</option>
                            <option value="percentage" {{ old('late_penalty_type', $setting->late_penalty_type) === 'percentage' ? 'selected' : '' }}>Persentase Gaji</option>
                            <option value="daily_rate" {{ old('late_penalty_type', $setting->late_penalty_type) === 'daily_rate' ? 'selected' : '' }}>Rate Harian</option>
                        </select>
                        @error('late_penalty_type')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-data="currencyInput({{ old('late_penalty_amount', $setting->late_penalty_amount ?? 0) }})">
                        <label for="late_penalty_amount_display" class="block text-sm font-medium text-secondary-700 mb-1">
                            Nominal Penalti
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" id="late_penalty_amount_display" x-model="display" @input="updateValue($event)"
                                   class="input w-full rounded-l-none @error('late_penalty_amount') border-danger-500 @enderror">
                            <input type="hidden" name="late_penalty_amount" :value="value">
                        </div>
                        @error('late_penalty_amount')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Pengaturan Lainnya --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pengaturan Lainnya</h3>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="prorate_new_employees" id="prorate_new_employees" value="1"
                               {{ old('prorate_new_employees', $setting->prorate_new_employees) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="prorate_new_employees" class="text-sm font-medium text-secondary-700">
                            Prorate gaji karyawan baru (masuk tengah bulan)
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="prorate_resigned_employees" id="prorate_resigned_employees" value="1"
                               {{ old('prorate_resigned_employees', $setting->prorate_resigned_employees) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="prorate_resigned_employees" class="text-sm font-medium text-secondary-700">
                            Prorate gaji karyawan resign (keluar tengah bulan)
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="include_overtime_in_payroll" id="include_overtime_in_payroll" value="1"
                               {{ old('include_overtime_in_payroll', $setting->include_overtime_in_payroll) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="include_overtime_in_payroll" class="text-sm font-medium text-secondary-700">
                            Hitung lembur otomatis dalam payroll
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="require_overtime_approval" id="require_overtime_approval" value="1"
                               {{ old('require_overtime_approval', $setting->require_overtime_approval) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="require_overtime_approval" class="text-sm font-medium text-secondary-700">
                            Lembur harus disetujui sebelum dihitung
                        </label>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="include_reimbursement_in_payroll" id="include_reimbursement_in_payroll" value="1"
                               {{ old('include_reimbursement_in_payroll', $setting->include_reimbursement_in_payroll ?? true) ? 'checked' : '' }}
                               class="w-4 h-4 text-primary-600 border-secondary-300 rounded focus:ring-primary-500">
                        <label for="include_reimbursement_in_payroll" class="text-sm font-medium text-secondary-700">
                            Masukkan reimbursement yang disetujui ke payroll
                        </label>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-secondary-700 mb-1">
                            Catatan
                        </label>
                        <textarea name="notes" id="notes" rows="3"
                                  class="input w-full @error('notes') border-danger-500 @enderror">{{ old('notes', $setting->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="submit" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>Simpan Pengaturan</span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
function currencyInput(initialValue) {
    return {
        value: initialValue || 0,
        display: initialValue ? new Intl.NumberFormat('id-ID').format(initialValue) : '',
        updateValue(event) {
            let raw = event.target.value.replace(/\D/g, '');
            this.value = parseInt(raw) || 0;
            this.display = this.value ? new Intl.NumberFormat('id-ID').format(this.value) : '';
        }
    }
}
</script>
@endpush
